<?php

namespace Tests\Feature\Admin;

use App\Models\Family;
use App\Models\Package;
use App\Models\PackagePurchaseRequest;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminOperationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function admin(): User
    {
        $admin = User::factory()->create(['role' => 'admin', 'pin_hash' => Hash::make('1234')]);
        $admin->assignRole('admin');

        return $admin;
    }

    private function member(array $attributes = []): User
    {
        $user = User::factory()->create(array_merge(['role' => 'member'], $attributes));
        $user->assignRole('member');

        return $user;
    }

    public function test_a_member_cannot_access_admin_routes(): void
    {
        $member = $this->member();

        $response = $this->actingAs($member)->getJson('/admin/dashboard');

        $response->assertStatus(403);
    }

    public function test_admin_can_grant_credits(): void
    {
        $admin = $this->admin();
        $member = $this->member(['remaining_lessons' => 2]);

        $response = $this->actingAs($admin)->postJson("/admin/members/{$member->id}/credits", [
            'delta' => 5,
            'note' => 'Telafi dersi',
        ]);

        $response->assertStatus(200);
        $this->assertSame(7, $member->fresh()->remaining_lessons);
        $this->assertDatabaseHas('credit_transactions', ['user_id' => $member->id, 'delta' => 5, 'reason' => 'admin_adjustment']);
    }

    public function test_admin_removing_more_credits_than_available_clamps_at_zero(): void
    {
        $admin = $this->admin();
        $member = $this->member(['remaining_lessons' => 2]);

        $response = $this->actingAs($admin)->postJson("/admin/members/{$member->id}/credits", ['delta' => -10]);

        $response->assertStatus(200);
        $this->assertSame(0, $member->fresh()->remaining_lessons);
    }

    public function test_approving_a_purchase_request_grants_credits_and_sets_active_package(): void
    {
        $admin = $this->admin();
        $member = $this->member(['remaining_lessons' => 0, 'total_lessons' => 0]);
        $package = Package::factory()->create(['lesson_count' => 20, 'price_try' => 29000]);
        $purchaseRequest = PackagePurchaseRequest::create([
            'user_id' => $member->id,
            'package_id' => $package->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->postJson("/admin/purchase-requests/{$purchaseRequest->id}/approve");

        $response->assertStatus(200);
        $this->assertSame(20, $member->fresh()->remaining_lessons);
        $this->assertSame(20, $member->fresh()->total_lessons);
        $this->assertSame($package->id, $member->fresh()->active_package_id);
        $this->assertSame('approved', $purchaseRequest->fresh()->status);
    }

    public function test_rejecting_a_purchase_request_grants_no_credits(): void
    {
        $admin = $this->admin();
        $member = $this->member(['remaining_lessons' => 0]);
        $package = Package::factory()->create(['lesson_count' => 20, 'price_try' => 29000]);
        $purchaseRequest = PackagePurchaseRequest::create([
            'user_id' => $member->id,
            'package_id' => $package->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->postJson("/admin/purchase-requests/{$purchaseRequest->id}/reject");

        $response->assertStatus(200);
        $this->assertSame(0, $member->fresh()->remaining_lessons);
        $this->assertSame('rejected', $purchaseRequest->fresh()->status);
    }

    public function test_approving_an_already_processed_request_is_rejected(): void
    {
        $admin = $this->admin();
        $member = $this->member();
        $package = Package::factory()->create(['lesson_count' => 20, 'price_try' => 29000]);
        $purchaseRequest = PackagePurchaseRequest::create([
            'user_id' => $member->id,
            'package_id' => $package->id,
            'status' => 'approved',
        ]);

        $response = $this->actingAs($admin)->postJson("/admin/purchase-requests/{$purchaseRequest->id}/approve");

        $response->assertStatus(422);
    }

    public function test_low_credit_dashboard_only_returns_members_with_contact_info(): void
    {
        $admin = $this->admin();
        $this->member(['remaining_lessons' => 1, 'phone' => '+90 500 000 00 01', 'name' => 'Contactable']);
        $this->member(['remaining_lessons' => 1, 'phone' => null, 'email' => null, 'name' => 'No Contact']);
        $this->member(['remaining_lessons' => 10, 'phone' => '+90 500 000 00 02', 'name' => 'Plenty Left']);

        $response = $this->actingAs($admin)->getJson('/admin/dashboard');

        $response->assertStatus(200);
        $names = collect($response->json('low_credit_members'))->pluck('name');
        $this->assertTrue($names->contains('Contactable'));
        $this->assertFalse($names->contains('No Contact'));
        $this->assertFalse($names->contains('Plenty Left'));
    }

    public function test_admin_can_add_and_remove_a_family_member(): void
    {
        $admin = $this->admin();
        $family = Family::factory()->create();
        $member = $this->member();

        $add = $this->actingAs($admin)->postJson("/admin/families/{$family->id}/members", ['user_id' => $member->id]);
        $add->assertStatus(201);
        $familyMemberId = $add->json('member.id');

        $this->assertDatabaseHas('family_members', ['family_id' => $family->id, 'user_id' => $member->id]);

        $remove = $this->actingAs($admin)->deleteJson("/admin/families/{$family->id}/members/{$familyMemberId}");
        $remove->assertStatus(200);
        $this->assertDatabaseMissing('family_members', ['id' => $familyMemberId]);
    }
}
