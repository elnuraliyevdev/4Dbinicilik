<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuthEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

/**
 * Intentionally no "clear logs" endpoint — deleting audit history on demand
 * (as the old prototype's fake panel offered) undermines the point of an
 * audit trail. Export exists instead for anyone who needs to archive/rotate.
 */
class AuditLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $events = AuthEvent::query()
            ->when($request->query('severity'), fn ($q, $s) => $q->where('severity', $s))
            ->when($request->query('type'), fn ($q, $t) => $q->where('event_type', $t))
            ->latest()
            ->paginate(50);

        return response()->json(['events' => $events]);
    }

    public function export(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $events = AuthEvent::query()->latest()->get();

        return Response::streamDownload(function () use ($events) {
            echo $events->toJson(JSON_PRETTY_PRINT);
        }, 'audit-log-'.now()->format('Y-m-d-His').'.json');
    }
}
