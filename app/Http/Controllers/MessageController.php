<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Message;
use App\Models\MessageRecipient;
use App\Models\User;
use App\Notifications\NewMessageNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class MessageController extends Controller
{
    public function index(Request $request): View
    {
        return $this->render($request);
    }

    public function show(Request $request, User $contact): View
    {
        return $this->render($request, $contact);
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

        // With a single recipient, land straight on that conversation;
        // with several (multiple/course mode) there's no one thread to
        // open, so just go back to the list — every recipient now has
        // their own row there.
        $redirect = $recipientIds->count() === 1
            ? redirect()->route('messages.show', $recipientIds->first())
            : redirect()->route('messages.index');

        return $redirect->with('status', 'Messaggio inviato.');
    }

    private function render(Request $request, ?User $contact = null): View
    {
        $user = $request->user();
        $canSend = $this->canSend($user);

        $conversations = $this->conversations($user, $canSend);

        $thread = null;

        if ($contact) {
            abort_unless($conversations->has($contact->id), 403);

            // Mark every still-unread message from this contact as read
            // in one go — opening the thread reads the whole exchange,
            // not just the single most recent message. (Read status is
            // only ever displayed for messages *we* sent, as a receipt —
            // never for our own received messages, so the in-memory
            // thread below doesn't need patching to reflect this.)
            MessageRecipient::where('user_id', $user->id)
                ->whereNull('read_at')
                ->whereHas('message', fn ($q) => $q->where('sender_id', $contact->id))
                ->update(['read_at' => now()]);

            $thread = $conversations->get($contact->id)['thread'];
        }

        return view('messages.index', [
            'conversations' => $conversations->values(),
            'contact' => $contact,
            'thread' => $thread,
            'canSend' => $canSend,
            'recipientPool' => $canSend ? $this->recipientPool($user) : collect(),
            'coursePool' => $canSend ? $this->coursePool($user) : collect(),
        ]);
    }

    /**
     * One entry per person the user has exchanged messages with — not
     * per message — each carrying the full chronological thread with
     * that person, admin-style "sent only" or instructor/member "both
     * directions merged" depending on canSend/role (see the class-level
     * scoping methods below, unchanged from before this view rework).
     *
     * @return Collection<int, array{counterpart: User, thread: Collection, latestAt: Carbon, unread: bool}>
     */
    private function conversations(User $user, bool $canSend): Collection
    {
        $entries = collect();

        if ($canSend) {
            Message::where('sender_id', $user->id)
                ->with('recipients.user')
                ->get()
                ->each(function (Message $message) use ($entries) {
                    foreach ($message->recipients as $recipient) {
                        $entries->push([
                            'counterpart' => $recipient->user,
                            'message' => $message,
                            'direction' => 'sent',
                            'at' => $message->created_at,
                            'read_at' => $recipient->read_at,
                        ]);
                    }
                });
        }

        // Admin never receives messages inside Monarch (see canSend()).
        if (! $user->hasRole('admin')) {
            MessageRecipient::where('user_id', $user->id)
                ->with('message.sender')
                ->get()
                ->each(function (MessageRecipient $recipient) use ($entries) {
                    $entries->push([
                        'counterpart' => $recipient->message->sender,
                        'message' => $recipient->message,
                        'direction' => 'received',
                        'at' => $recipient->message->created_at,
                        'read_at' => $recipient->read_at,
                    ]);
                });
        }

        return $entries
            ->groupBy(fn (array $entry) => $entry['counterpart']->id)
            ->map(function (Collection $group) {
                // Newest first, both here (the thread itself) and in the
                // conversation list below — not the "oldest first" order
                // a typical email thread reads in.
                $thread = $group->sortByDesc('at')->values();
                $latest = $thread->first();

                return [
                    'counterpart' => $latest['counterpart'],
                    'thread' => $thread,
                    'latestAt' => $latest['at'],
                    'unread' => $thread->contains(fn (array $e) => $e['direction'] === 'received' && ! $e['read_at']),
                ];
            })
            ->sortByDesc('latestAt');
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
