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
        return [WebPushChannel::class];
    }

    public function toWebPush(object $notifiable, self $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('Pagamento registrato')
            ->icon('/icons/icon-192.png')
            ->body('€'.number_format((float) $this->payment->amount, 2).' · '.$this->payment->enrollment->course->discipline->name)
            ->data(['url' => route('member.area')]);
    }
}
