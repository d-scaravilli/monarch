<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Message;
use App\Models\MessageRecipient;
use App\Models\User;
use App\Notifications\NewMessageNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class MessageController extends Controller
{
    public function index(Request $request): View
    {
        return $this->render($request);
    }

    public function show(Request $request, Message $message): View
    {
        return $this->render($request, $message);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($this->canSend($user), 403);

        $data = $request->validate([
            'recipient_mode' => 'required|in:single,multiple,course',
            'recipient_id' => 'nullable|integer|exists:users,id',
            'recipient_ids' => 'nullable|array',
            'recipient_ids.*' => 'integer|exists:users,id',
            'course_id' => 'nullable|integer|exists:courses,id',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
        ]);

        $pool = $this->recipientPool($user)->pluck('id');

        $recipientIds = match ($data['recipient_mode']) {
            'single' => collect([$data['recipient_id'] ?? null])->filter(),
            'multiple' => collect($data['recipient_ids'] ?? []),
            'course' => $this->courseEnrollmentIds($user, (int) ($data['course_id'] ?? 0)),
        };

        $recipientIds = $recipientIds->intersect($pool)->unique()->values();

        abort_if($recipientIds->isEmpty(), 422, 'Nessun destinatario valido selezionato.');

        $message = Message::create([
            'sender_id' => $user->id,
            'subject' => $data['subject'],
            'body' => $data['body'],
        ]);

        foreach ($recipientIds as $recipientId) {
            $message->recipients()->create(['user_id' => $recipientId]);
        }

        User::whereIn('id', $recipientIds)->get()->each(
            fn (User $recipient) => $recipient->notify(new NewMessageNotification($message))
        );

        return redirect()->route('messages.show', $message)->with('status', 'Messaggio inviato.');
    }

    private function render(Request $request, ?Message $message = null): View
    {
        $user = $request->user();
        $canSend = $this->canSend($user);

        $box = $canSend && $request->query('box') === 'sent' ? 'sent' : 'received';

        if ($box === 'sent') {
            $list = Message::where('sender_id', $user->id)
                ->withCount('recipients')
                ->withCount(['recipients as read_recipients_count' => fn ($q) => $q->whereNotNull('read_at')])
                ->latest()
                ->get();
        } else {
            $list = MessageRecipient::where('user_id', $user->id)
                ->with('message.sender')
                ->latest()
                ->get();
        }

        $selectedMessage = null;
        $readReceipts = null;

        if ($message) {
            $isSender = $message->sender_id === $user->id;
            $recipientRow = $message->recipients()->where('user_id', $user->id)->first();

            abort_unless($isSender || $recipientRow, 403);

            if ($recipientRow && ! $recipientRow->read_at) {
                $recipientRow->update(['read_at' => now()]);
            }

            $selectedMessage = $message->load('sender');

            if ($isSender) {
                $readReceipts = $message->recipients()->with('user')->orderBy('user_id')->get();
            }
        }

        return view('messages.index', [
            'list' => $list,
            'box' => $box,
            'canSend' => $canSend,
            'selectedMessage' => $selectedMessage,
            'readReceipts' => $readReceipts,
            'recipientPool' => $canSend ? $this->recipientPool($user) : collect(),
            'coursePool' => $canSend ? $this->coursePool($user) : collect(),
        ]);
    }

    private function canSend(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasRole('instructor');
    }

    /**
     * Admin can message anyone in the module (members and instructors
     * alike — an instructor is still a person who can be messaged).
     * An instructor can only message people actually enrolled in one of
     * the courses/eventi they're assigned to teach — the same scoping
     * already used for Team/Progressi.
     */
    private function recipientPool(User $user): Collection
    {
        if ($user->hasRole('admin')) {
            return User::role(['member', 'instructor'])->orderBy('name')->get();
        }

        $courseIds = $user->instructedCourses()->pluck('courses.id');

        return User::whereHas('enrollments', fn ($q) => $q->whereIn('course_id', $courseIds))
            ->orderBy('name')
            ->distinct()
            ->get();
    }

    private function coursePool(User $user): Collection
    {
        if ($user->hasRole('admin')) {
            return Course::with('discipline')->orderByDesc('year')->get();
        }

        return $user->instructedCourses()->with('discipline')->orderByDesc('year')->get();
    }

    /**
     * @return Collection<int, int>
     */
    private function courseEnrollmentIds(User $user, int $courseId): Collection
    {
        $allowed = $this->coursePool($user)->pluck('id');
        abort_unless($allowed->contains($courseId), 403);

        return Course::findOrFail($courseId)->enrollments()->pluck('user_id');
    }
}
