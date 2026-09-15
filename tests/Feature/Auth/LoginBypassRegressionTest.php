<?php

namespace Tests\Feature\Auth;

use App\Models\Trainer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Regression tests for the exact auth bypasses the old static prototype had:
 * member login accepted any string longer than 3 chars, admin login accepted
 * any non-empty password as long as a hardcoded PIN matched (and echoed that
 * PIN in the failure toast), trainer login accepted any non-empty PIN.
 */
class LoginBypassRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_login_rejects_an_arbitrary_string_that_is_not_the_real_password(): void
    {
        User::factory()->create([
            'role' => 'member',
            'phone' => '+90 500 000 00 99',
            'password' => Hash::make('the-real-password'),
        ]);

        // The old bug: `phoneOrRef.length > 3` alone was enough to log in.
        $response = $this->postJson('/login/member', [
            'identifier' => '+90 500 000 00 99',
            'password' => 'some-arbitrary-string-over-3-chars',
        ]);

        $response->assertStatus(422);
        $this->assertGuest();
    }

    public function test_member_with_no_password_set_cannot_log_in_silently(): void
    {
        User::factory()->create([
            'role' => 'member',
            'ref_code' => 'REF-1',
            'password' => null,
        ]);

        $response = $this->postJson('/login/member', [
            'identifier' => 'REF-1',
            'password' => 'anything',
        ]);

        $response->assertStatus(403);
        $this->assertGuest();
    }

    public function test_admin_login_rejects_correct_pin_with_wrong_password(): void
    {
        User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@club.test',
            'password' => Hash::make('the-real-password'),
            'pin_hash' => Hash::make('1450'),
        ]);

        // The old bug: `pass.length > 0` meant ANY non-empty password worked
        // as long as the PIN matched.
        $response = $this->postJson('/login/admin', [
            'email' => 'admin@club.test',
            'password' => 'totally-wrong-password',
            'pin' => '1450',
        ]);

        $response->assertStatus(422);
        $response->assertJsonMissing(['pin' => '1450']);
        $this->assertStringNotContainsString('1450', $response->getContent());
        $this->assertGuest();
    }

    public function test_admin_login_rejects_correct_password_with_wrong_pin(): void
    {
        User::factory()->create([
            'role' => 'admin',
            'email' => 'admin2@club.test',
            'password' => Hash::make('the-real-password'),
            'pin_hash' => Hash::make('1450'),
        ]);

        $response = $this->postJson('/login/admin', [
            'email' => 'admin2@club.test',
            'password' => 'the-real-password',
            'pin' => '0000',
        ]);

        $response->assertStatus(422);
        $this->assertGuest();
    }

    public function test_admin_login_succeeds_with_correct_password_and_pin(): void
    {
        User::factory()->create([
            'role' => 'admin',
            'email' => 'admin3@club.test',
            'password' => Hash::make('the-real-password'),
            'pin_hash' => Hash::make('1450'),
        ]);

        $response = $this->postJson('/login/admin', [
            'email' => 'admin3@club.test',
            'password' => 'the-real-password',
            'pin' => '1450',
        ]);

        $response->assertStatus(200);
        $this->assertAuthenticated();
    }

    public function test_trainer_login_rejects_arbitrary_non_empty_pin(): void
    {
        $user = User::factory()->create([
            'role' => 'trainer',
            'pin_hash' => Hash::make('9999'),
        ]);
        $trainer = Trainer::factory()->create(['user_id' => $user->id]);

        // The old bug: `pin.length > 0` meant any non-empty value logged the trainer in.
        $response = $this->postJson('/login/trainer', [
            'trainer_id' => $trainer->id,
            'pin' => 'anything-non-empty',
        ]);

        $response->assertStatus(422);
        $this->assertGuest();
    }

    public function test_five_failed_attempts_locks_out_the_sixth(): void
    {
        User::factory()->create([
            'role' => 'member',
            'ref_code' => 'REF-LOCK',
            'password' => Hash::make('the-real-password'),
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/login/member', [
                'identifier' => 'REF-LOCK',
                'password' => 'wrong',
            ])->assertStatus(422);
        }

        $response = $this->postJson('/login/member', [
            'identifier' => 'REF-LOCK',
            'password' => 'wrong',
        ]);

        $response->assertStatus(429);
    }
}
