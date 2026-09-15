<?php

namespace App\Notifications;

use App\Models\MemberNote;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class NoteAddedNotification extends Notification
{
    use Queueable;

    public function __construct(public MemberNote $note)
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
            ->title('Nuova nota')
            ->icon('/icons/icon-192.png')
            ->body(Str::limit($this->note->description, 100))
            ->data(['url' => route('member.area')]);
    }

    /**
     * Feeds the notification bell dropdown (see x-notification-bell).
     *
     * @return array<string, string>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Nuova nota',
            'body' => Str::limit($this->note->description, 100),
            'url' => route('member.area'),
        ];
    }
}
