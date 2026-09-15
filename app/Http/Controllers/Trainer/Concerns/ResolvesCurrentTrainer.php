<?php

namespace App\Http\Controllers\Trainer\Concerns;

use App\Models\Trainer;
use Illuminate\Http\Request;

trait ResolvesCurrentTrainer
{
    /**
     * The `trainer|admin` route middleware lets admins through too, but these
     * endpoints only make sense for an actual trainer profile — an admin
     * hitting them directly (rather than through Phase 4's admin endpoints)
     * gets a clear 404 instead of silently acting as no one's schedule.
     */
    private function resolveTrainer(Request $request): Trainer
    {
        return Trainer::query()->where('user_id', $request->user()->id)->firstOrFail();
    }
}
