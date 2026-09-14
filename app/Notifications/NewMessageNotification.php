<?php

namespace App\Notifications;

use App\Models\Message;
use Illuminate\Bus\Queueable;
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
        return [WebPushChannel::class];
    }

    public function toWebPush(object $notifiable, self $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('Nuovo messaggio: '.$this->message->subject)
            ->icon('/icons/icon-192.png')
            ->body($this->message->sender->name.': '.Str::limit($this->message->body, 100))
            ->data(['url' => route('messages.show', $this->message->sender)]);
    }
}
