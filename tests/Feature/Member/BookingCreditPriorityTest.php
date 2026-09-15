<?php

namespace Tests\Feature\Member;

use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\Trainer;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class BookingCreditPriorityTest extends TestCase
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

    private function bookingPayload(Trainer $trainer): array
    {
        $date = Carbon::now()->isMonday() ? Carbon::now()->addDay() : Carbon::now();

        return [
            'trainer_id' => $trainer->id,
            'date' => $date->toDateString(),
            'time' => '09:00',
        ];
    }

    public function test_booking_deducts_from_individual_balance_first(): void
    {
        $user = $this->member(['remaining_lessons' => 5]);
        $trainer = Trainer::factory()->create();

        $response = $this->actingAs($user)->postJson('/member/reservations', $this->bookingPayload($trainer));

        $response->assertStatus(201);
        $this->assertSame(4, $user->fresh()->remaining_lessons);
        $this->assertDatabaseHas('credit_transactions', [
            'user_id' => $user->id,
            'family_id' => null,
            'delta' => -1,
        ]);
    }

    public function test_booking_falls_back_to_family_pool_when_individual_is_zero(): void
    {
        $user = $this->member(['remaining_lessons' => 0]);
        $family = Family::factory()->create(['remaining_lessons' => 10]);
        FamilyMember::create(['family_id' => $family->id, 'user_id' => $user->id]);
        $trainer = Trainer::factory()->create();

        $response = $this->actingAs($user)->postJson('/member/reservations', $this->bookingPayload($trainer));

        $response->assertStatus(201);
        $this->assertSame(0, $user->fresh()->remaining_lessons);
        $this->assertSame(9, $family->fresh()->remaining_lessons);
        $this->assertSame(1, $family->fresh()->reserved_lessons);
        $this->assertDatabaseHas('credit_transactions', [
            'user_id' => $user->id,
            'family_id' => $family->id,
            'delta' => -1,
        ]);
    }

    public function test_booking_fails_when_both_individual_and_family_are_zero(): void
    {
        $user = $this->member(['remaining_lessons' => 0]);
        $family = Family::factory()->create(['remaining_lessons' => 0]);
        FamilyMember::create(['family_id' => $family->id, 'user_id' => $user->id]);
        $trainer = Trainer::factory()->create();

        $response = $this->actingAs($user)->postJson('/member/reservations', $this->bookingPayload($trainer));

        $response->assertStatus(422);
        $this->assertDatabaseCount('reservations', 0);
    }

    public function test_double_booking_the_same_slot_is_rejected(): void
    {
        $trainer = Trainer::factory()->create();
        $first = $this->member(['remaining_lessons' => 5]);
        $second = $this->member(['remaining_lessons' => 5]);
        $payload = $this->bookingPayload($trainer);

        $this->actingAs($first)->postJson('/member/reservations', $payload)->assertStatus(201);
        $response = $this->actingAs($second)->postJson('/member/reservations', $payload);

        $response->assertStatus(409);
        $this->assertSame(5, $second->fresh()->remaining_lessons);
    }
}
