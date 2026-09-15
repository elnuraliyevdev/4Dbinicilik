<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Trainer\Concerns\ResolvesCurrentTrainer;
use App\Http\Requests\Trainer\FeedbackRequest;
use App\Models\AuthEvent;
use App\Models\Reservation;
use App\Models\TrainerFeedbackNote;
use Illuminate\Http\JsonResponse;

class FeedbackController extends Controller
{
    use ResolvesCurrentTrainer;

    public function store(FeedbackRequest $request): JsonResponse
    {
        $trainer = $this->resolveTrainer($request);

        // student_user_id/reservation_id are only validated as "exist somewhere"
        // by the FormRequest — without this, any trainer could attach a note to
        // any other trainer's reservation/student by guessing IDs.
        $reservation = Reservation::findOrFail($request->validated('reservation_id'));
        abort_unless(
            $reservation->trainer_id === $trainer->id
                && $reservation->user_id === (int) $request->validated('student_user_id'),
            403
        );

        $note = TrainerFeedbackNote::create([
            'trainer_id' => $trainer->id,
            'student_user_id' => $request->validated('student_user_id'),
            'reservation_id' => $request->validated('reservation_id'),
            'discipline_level' => $request->validated('discipline_level'),
            'note' => $request->validated('note'),
        ]);

        AuthEvent::log('TRAINER_FEEDBACK_ADDED', 'info', "{$trainer->user->name} bir öğrenciye gelişim notu ekledi", [
            'user_id' => $request->user()->id,
            'role' => 'trainer',
        ]);

        return response()->json(['note' => $note], 201);
    }
}
