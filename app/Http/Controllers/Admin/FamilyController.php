<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AddFamilyMemberRequest;
use App\Http\Requests\Admin\StoreFamilyRequest;
use App\Models\AuthEvent;
use App\Models\Family;
use App\Models\FamilyMember;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FamilyController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'families' => Family::query()->with('members.user')->orderBy('name')->get(),
        ]);
    }

    public function store(StoreFamilyRequest $request): JsonResponse
    {
        $family = Family::create([
            'name' => $request->validated('name'),
            'package_total' => $request->validated('package_total', 0),
        ]);

        AuthEvent::log('FAMILY_CREATED', 'info', "Admin {$request->user()->name} — yeni aile grubu: {$family->name}", [
            'user_id' => $request->user()->id,
            'role' => 'admin',
        ]);

        return response()->json(['family' => $family], 201);
    }

    public function addMember(AddFamilyMemberRequest $request, Family $family): JsonResponse
    {
        $member = FamilyMember::create([
            'family_id' => $family->id,
            'user_id' => $request->validated('user_id'),
            'display_name' => $request->validated('display_name'),
            'is_primary' => $request->boolean('is_primary'),
        ]);

        if ($member->is_primary) {
            $family->update(['primary_user_id' => $member->user_id]);
        }

        return response()->json(['member' => $member->load('user')], 201);
    }

    public function removeMember(Request $request, Family $family, FamilyMember $familyMember): JsonResponse
    {
        abort_unless($familyMember->family_id === $family->id, 404);

        $familyMember->delete();

        AuthEvent::log('FAMILY_MEMBER_REMOVED', 'info', "Admin {$request->user()->name} — {$family->name} grubundan üye çıkardı", [
            'user_id' => $request->user()->id,
            'role' => 'admin',
        ]);

        return response()->json(['message' => 'Üye aile grubundan çıkarıldı.']);
    }
}
