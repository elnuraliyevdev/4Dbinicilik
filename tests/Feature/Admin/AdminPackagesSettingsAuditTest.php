<?php

namespace Tests\Feature\Admin;

use App\Models\ClubSetting;
use App\Models\Package;
use App\Models\SafariTour;
use App\Models\Trainer;
use App\Models\User;
use Database\Seeders\ClubSettingsSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Covers the admin package/safari-tour CRUD, club settings, and audit log
 * endpoints — previously exercised by nothing at all (confirmed by grepping
 * tests/ before writing this), plus the one assumption every other admin
 * test leaves unverified: that role:admin actually excludes a *trainer*
 * account specifically, not just a member.
 */
class AdminPackagesSettingsAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(ClubSettingsSeeder::class);
    }

    private function admin(): User
    {
        $admin = User::factory()->create(['role' => 'admin', 'pin_hash' => Hash::make('1234')]);
        $admin->assignRole('admin');

        return $admin;
    }

    private function trainer(): User
    {
        $trainer = Trainer::factory()->create();
        $trainer->user->assignRole('trainer');

        return $trainer->user;
    }

    public function test_admin_can_update_a_package_price(): void
    {
        $package = Package::factory()->create(['price_try' => 1000]);

        $response = $this->actingAs($this->admin())->patchJson("/admin/packages/{$package->id}", ['price_try' => 1500]);

        $response->assertStatus(200);
        $this->assertSame('1500.00', $package->fresh()->price_try);
    }

    public function test_a_new_package_cannot_have_a_negative_or_zero_price(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->postJson('/admin/packages', ['lesson_count' => 4, 'price_try' => -100])
            ->assertStatus(422);

        $this->actingAs($admin)->postJson('/admin/packages', ['lesson_count' => 0, 'price_try' => 5000])
            ->assertStatus(422);
    }

    public function test_admin_can_create_a_safari_tour_and_a_trainer_cannot(): void
    {
        $payload = [
            'name' => 'Test Safari Turu', 'duration_minutes' => 60,
            'price_per_person' => 1500, 'description' => 'test',
        ];

        $this->actingAs($this->trainer())->postJson('/admin/safari-tours', $payload)->assertStatus(403);

        $response = $this->actingAs($this->admin())->postJson('/admin/safari-tours', $payload);
        $response->assertStatus(201);
        $this->assertDatabaseHas('safari_tours', ['name' => 'Test Safari Turu']);
    }

    public function test_cancellation_window_hours_rejects_negative_and_non_numeric_values(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->putJson('/admin/settings/cancellation_window_hours', ['value' => '-5'])
            ->assertStatus(422);
        $this->actingAs($admin)->putJson('/admin/settings/cancellation_window_hours', ['value' => 'abc'])
            ->assertStatus(422);

        // A valid value is accepted and actually changes real booking behavior
        // downstream (Reservation::cancellationWindowHours()), not just the
        // stored string — this is the assertion the missing-validation bug
        // itself had zero coverage for.
        $this->actingAs($admin)->putJson('/admin/settings/cancellation_window_hours', ['value' => '4'])
            ->assertStatus(200);
        $this->assertSame('4', ClubSetting::get('cancellation_window_hours'));
    }

    public function test_settings_endpoint_rejects_an_unrecognized_key(): void
    {
        $response = $this->actingAs($this->admin())->putJson('/admin/settings/some_made_up_typo_key', ['value' => 'x']);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('club_settings', ['key' => 'some_made_up_typo_key']);
    }

    public function test_audit_log_is_admin_only_and_a_trainer_is_rejected(): void
    {
        $this->actingAs($this->trainer())->getJson('/admin/audit-log')->assertStatus(403);
        $this->actingAs($this->admin())->getJson('/admin/audit-log')->assertStatus(200);
    }

    public function test_a_trainer_cannot_reach_admin_packages_or_settings_or_purchase_requests(): void
    {
        $trainer = $this->trainer();

        $this->actingAs($trainer)->getJson('/admin/packages')->assertStatus(403);
        $this->actingAs($trainer)->getJson('/admin/settings')->assertStatus(403);
        $this->actingAs($trainer)->getJson('/admin/purchase-requests')->assertStatus(403);
        $this->actingAs($trainer)->getJson('/admin/safari-tours')->assertStatus(403);
    }
}
