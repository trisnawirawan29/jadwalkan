<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SystemNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $title,
        private readonly string $message,
        private readonly string $type = 'info',
        private readonly string $source = 'system',
        private readonly ?int $bookingId = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return array_filter([
            'title' => $this->title,
            'message' => $this->message,
            'type' => $this->type,
            'source' => $this->source,
            'booking_id' => $this->bookingId,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
