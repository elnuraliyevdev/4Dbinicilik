<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ClaimAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_unsigned_claim_link_is_rejected(): void
    {
        $user = User::factory()->create(['password' => null]);

        $response = $this->getJson("/claim-account/{$user->id}");

        $response->assertStatus(403);
    }

    public function test_signed_link_lets_a_passwordless_member_set_a_password(): void
    {
        $user = User::factory()->create(['password' => null, 'phone' => '+90 500 000 09 09']);

        $url = URL::temporarySignedRoute('claim-account.store', now()->addDay(), ['user' => $user->id]);
        $path = parse_url($url, PHP_URL_PATH).'?'.parse_url($url, PHP_URL_QUERY);

        $response = $this->postJson($path, [
            'password' => 'a-strong-password',
            'password_confirmation' => 'a-strong-password',
        ]);

        $response->assertStatus(200);
        $this->assertNotNull($user->fresh()->password);
    }

    public function test_an_already_claimed_account_cannot_be_claimed_again(): void
    {
        $user = User::factory()->create(); // has a password by default via factory

        $url = URL::temporarySignedRoute('claim-account.store', now()->addDay(), ['user' => $user->id]);
        $path = parse_url($url, PHP_URL_PATH).'?'.parse_url($url, PHP_URL_QUERY);

        $response = $this->postJson($path, [
            'password' => 'a-strong-password',
            'password_confirmation' => 'a-strong-password',
        ]);

        $response->assertStatus(409);
    }
}
