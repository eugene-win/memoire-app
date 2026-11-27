<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class ThesisWorkflowNotification extends Notification
{
    public function __construct(
        private readonly string $event,
        private readonly string $title,
        private readonly string $message,
        private readonly int $thesisId,
        private readonly int $versionId,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'event' => $this->event,
            'title' => $this->title,
            'message' => $this->message,
            'thesis_id' => $this->thesisId,
            'version_id' => $this->versionId,
        ];
    }
}
