<?php

namespace Tests\Feature\Trainer;

use App\Models\CreditTransaction;
use App\Models\Reservation;
use App\Models\Trainer;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TrainerPortalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function trainer(): Trainer
    {
        $trainer = Trainer::factory()->create();
        $trainer->user->assignRole('trainer');

        return $trainer;
    }

    private function bookedReservation(Trainer $trainer, User $student, int $creditDelta = -1): Reservation
    {
        $reservation = Reservation::create([
            'reservation_code' => 'RES-'.Str::upper(Str::random(8)),
            'user_id' => $student->id,
            'trainer_id' => $trainer->id,
            'type' => 'lesson',
            'activity_label' => 'Manej Dersi',
            'date' => now()->toDateString(),
            'time' => '10:00',
            'status' => 'confirmed',
            'source' => 'member',
        ]);

        CreditTransaction::create([
            'user_id' => $student->id,
            'delta' => $creditDelta,
            'reason' => 'booking',
            'reference_type' => Reservation::class,
            'reference_id' => $reservation->id,
        ]);
        $student->decrement('remaining_lessons', abs($creditDelta));
        $student->increment('pending_lessons', abs($creditDelta));

        return $reservation;
    }

    public function test_trainer_sees_only_their_own_schedule(): void
    {
        $trainer = $this->trainer();
        $otherTrainer = $this->trainer();
        $student = User::factory()->create(['role' => 'member', 'remaining_lessons' => 5]);

        $mine = $this->bookedReservation($trainer, $student);
        $this->bookedReservation($otherTrainer, $student);

        $response = $this->actingAs($trainer->user)->getJson('/trainer/schedule?date='.now()->toDateString());

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'reservations');
        $response->assertJsonPath('reservations.0.id', $mine->id);
    }

    public function test_trainer_cannot_mark_attendance_for_another_trainers_reservation(): void
    {
        $trainer = $this->trainer();
        $otherTrainer = $this->trainer();
        $student = User::factory()->create(['role' => 'member', 'remaining_lessons' => 5]);
        $reservation = $this->bookedReservation($otherTrainer, $student);

        $response = $this->actingAs($trainer->user)->postJson("/trainer/reservations/{$reservation->id}/attendance", ['status' => 'completed']);

        $response->assertStatus(403);
    }

    public function test_marking_completed_consumes_credit_permanently(): void
    {
        $trainer = $this->trainer();
        $student = User::factory()->create(['role' => 'member', 'remaining_lessons' => 4, 'used_lessons' => 0]);
        $reservation = $this->bookedReservation($trainer, $student);

        $response = $this->actingAs($trainer->user)->postJson("/trainer/reservations/{$reservation->id}/attendance", ['status' => 'completed']);

        $response->assertStatus(200);
        $this->assertSame('completed', $reservation->fresh()->status);
        $this->assertSame(3, $student->fresh()->remaining_lessons, 'attendance never refunds the credit — stays at the post-booking balance');
        $this->assertSame(1, $student->fresh()->used_lessons);
        $this->assertSame(0, $student->fresh()->pending_lessons);
    }

    public function test_marking_no_show_consumes_credit_permanently(): void
    {
        $trainer = $this->trainer();
        $student = User::factory()->create(['role' => 'member', 'remaining_lessons' => 4]);
        $reservation = $this->bookedReservation($trainer, $student);

        $response = $this->actingAs($trainer->user)->postJson("/trainer/reservations/{$reservation->id}/attendance", ['status' => 'no_show']);

        $response->assertStatus(200);
        $this->assertSame('no_show', $reservation->fresh()->status);
        $this->assertSame(3, $student->fresh()->remaining_lessons);
        $this->assertSame(1, $student->fresh()->used_lessons);
    }

    public function test_trainer_can_add_a_feedback_note(): void
    {
        $trainer = $this->trainer();
        $student = User::factory()->create(['role' => 'member']);
        $reservation = $this->bookedReservation($trainer, $student);

        $response = $this->actingAs($trainer->user)->postJson('/trainer/feedback', [
            'student_user_id' => $student->id,
            'reservation_id' => $reservation->id,
            'discipline_level' => 'Temel Denge & Oturuş (Manejde Başarılı)',
            'note' => 'Denge iyi, tırısta biraz daha çalışmalı.',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('trainer_feedback_notes', [
            'trainer_id' => $trainer->id,
            'student_user_id' => $student->id,
            'discipline_level' => 'Temel Denge & Oturuş (Manejde Başarılı)',
        ]);
    }

    public function test_trainer_can_close_a_slot_and_it_blocks_new_bookings(): void
    {
        $trainer = $this->trainer();
        $member = User::factory()->create(['role' => 'member', 'remaining_lessons' => 5]);
        $member->assignRole('member');
        $date = now()->isMonday() ? now()->addDay()->toDateString() : now()->toDateString();

        $toggle = $this->actingAs($trainer->user)->postJson('/trainer/slots/toggle', [
            'date' => $date,
            'time' => '09:00',
            'status' => 'off',
        ]);
        $toggle->assertStatus(200);

        $booking = $this->actingAs($member)->postJson('/member/reservations', [
            'trainer_id' => $trainer->id,
            'date' => $date,
            'time' => '09:00',
        ]);

        $booking->assertStatus(409);
    }
}
