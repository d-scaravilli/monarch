<?php

namespace App\Notifications;

use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class NewMessageNotification extends Notification
{
    use Queueable;

    public function __construct(public Message $message)
    {
        //
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return [WebPushChannel::class, 'database'];
    }

    public function toWebPush(object $notifiable, self $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('Nuovo messaggio: '.$this->message->subject)
            ->icon('/icons/icon-192.png')
            ->body($this->message->sender->name.': '.Str::limit($this->message->body, 100))
            ->data(['url' => route('messages.show', $this->message->sender)]);
    }

    /**
     * Feeds the notification bell dropdown (see x-notification-bell).
     * `message_id` isn't shown anywhere but lets deleteFor() below find
     * every recipient's copy of this notification when the message is
     * later deleted.
     *
     * @return array<string, string|int>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message_id' => $this->message->id,
            'title' => 'Nuovo messaggio: '.$this->message->subject,
            'body' => $this->message->sender->name.': '.Str::limit($this->message->body, 100),
            'url' => route('messages.show', $this->message->sender),
        ];
    }

    /**
     * Deletes every recipient's stored notification for the given
     * message(s) — there's one database row per recipient, not one per
     * message, so this must not be scoped to a single notifiable.
     * Notifications sent before message_id was added to toArray() above
     * simply won't match and are left alone.
     */
    public static function deleteFor(int|array $messageIds): void
    {
        DatabaseNotification::where('type', self::class)
            ->whereIn('data->message_id', (array) $messageIds)
            ->delete();
    }
}
