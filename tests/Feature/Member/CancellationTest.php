<?php

namespace Tests\Feature\Member;

use App\Models\CreditTransaction;
use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\Reservation;
use App\Models\SafariTour;
use App\Models\Trainer;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class CancellationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function member(array $attributes = []): User
    {
        $user = User::factory()->create(array_merge(['role' => 'member'], $attributes));
        $user->assignRole('member');

        return $user;
    }

    private function bookLesson(User $user, Trainer $trainer, Carbon $startsAt, ?Family $family = null): Reservation
    {
        $reservation = Reservation::create([
            'reservation_code' => 'RES-'.Str::upper(Str::random(8)),
            'user_id' => $user->id,
            'trainer_id' => $trainer->id,
            'type' => 'lesson',
            'activity_label' => 'Manej Dersi',
            'date' => $startsAt->toDateString(),
            'time' => $startsAt->format('H:i'),
            'status' => 'confirmed',
            'source' => 'member',
        ]);

        CreditTransaction::create([
            'user_id' => $user->id,
            'family_id' => $family?->id,
            'delta' => -1,
            'reason' => 'booking',
            'reference_type' => Reservation::class,
            'reference_id' => $reservation->id,
        ]);

        if ($family) {
            $family->decrement('remaining_lessons');
            $family->increment('reserved_lessons');
        } else {
            $user->decrement('remaining_lessons');
        }
        $user->increment('pending_lessons');

        return $reservation;
    }

    public function test_free_cancellation_outside_window_refunds_individual_balance(): void
    {
        $user = $this->member(['remaining_lessons' => 4, 'pending_lessons' => 0]);
        $trainer = Trainer::factory()->create();
        $reservation = $this->bookLesson($user, $trainer, now()->addHours(5));

        $response = $this->actingAs($user)->deleteJson("/member/reservations/{$reservation->id}");

        $response->assertStatus(200);
        $this->assertSame(4, $user->fresh()->remaining_lessons);
        $this->assertSame(0, $user->fresh()->pending_lessons);
        $this->assertSame('cancelled', $reservation->fresh()->status);
    }

    public function test_free_cancellation_refunds_family_pool_when_that_was_the_source(): void
    {
        $user = $this->member(['remaining_lessons' => 0]);
        $family = Family::factory()->create(['remaining_lessons' => 10, 'reserved_lessons' => 0]);
        FamilyMember::create(['family_id' => $family->id, 'user_id' => $user->id]);
        $trainer = Trainer::factory()->create();
        $reservation = $this->bookLesson($user, $trainer, now()->addHours(5), $family);

        $response = $this->actingAs($user)->deleteJson("/member/reservations/{$reservation->id}");

        $response->assertStatus(200);
        $this->assertSame(10, $family->fresh()->remaining_lessons);
        $this->assertSame(0, $family->fresh()->reserved_lessons);
        $this->assertSame(0, $user->fresh()->remaining_lessons, 'refund must not leak into the individual balance');
    }

    public function test_free_cancellation_inside_window_is_rejected(): void
    {
        $user = $this->member(['remaining_lessons' => 4]);
        $trainer = Trainer::factory()->create();
        $reservation = $this->bookLesson($user, $trainer, now()->addHour());

        $response = $this->actingAs($user)->deleteJson("/member/reservations/{$reservation->id}");

        $response->assertStatus(422);
        $this->assertSame('confirmed', $reservation->fresh()->status);
    }

    public function test_late_cancellation_inside_window_consumes_credit_without_refund(): void
    {
        $user = $this->member(['remaining_lessons' => 4, 'used_lessons' => 0]);
        $trainer = Trainer::factory()->create();
        $reservation = $this->bookLesson($user, $trainer, now()->addMinutes(30));

        $response = $this->actingAs($user)->postJson("/member/reservations/{$reservation->id}/late-cancel");

        $response->assertStatus(200);
        $this->assertSame(3, $user->fresh()->remaining_lessons, 'no refund on a late cancellation — stays at the post-booking balance');
        $this->assertSame(1, $user->fresh()->used_lessons);
        $this->assertSame('late_cancelled', $reservation->fresh()->status);
    }

    public function test_safari_tour_uses_its_own_cancellation_window_override(): void
    {
        // Club-wide default is 2h, but this tour requires 24h notice.
        $tour = SafariTour::factory()->create(['cancellation_hours' => 24]);
        $user = $this->member();

        $reservation = Reservation::create([
            'reservation_code' => 'SAF-'.Str::upper(Str::random(8)),
            'user_id' => $user->id,
            'safari_tour_id' => $tour->id,
            'type' => 'safari',
            'activity_label' => $tour->name,
            'date' => now()->addHours(5)->toDateString(),
            'time' => now()->addHours(5)->format('H:i'),
            'status' => 'confirmed',
            'source' => 'member',
            'participants' => 2,
            'price_try' => 3000,
        ]);

        // 5 hours out would pass the generic 2h rule but must fail the tour's 24h rule.
        $response = $this->actingAs($user)->deleteJson("/member/reservations/{$reservation->id}");

        $response->assertStatus(422);
        $response->assertJson(['late_cancel_required' => true]);
    }
}
