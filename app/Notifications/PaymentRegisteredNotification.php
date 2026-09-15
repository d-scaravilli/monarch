<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class PaymentRegisteredNotification extends Notification
{
    use Queueable;

    public function __construct(public Payment $payment)
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
            ->title('Pagamento registrato')
            ->icon('/icons/icon-192.png')
            ->body($this->body())
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
            'title' => 'Pagamento registrato',
            'body' => $this->body(),
            'url' => route('member.area'),
        ];
    }

    private function body(): string
    {
        return '€'.number_format((float) $this->payment->amount, 2).' · '.$this->payment->enrollment->course->discipline->name;
    }
}
