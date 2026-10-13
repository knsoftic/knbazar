<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Base class for marketplace notifications. Every notification is stored in
 * the database (in-app bell) and optionally emailed when the admin enables
 * email notifications. Subclasses only describe their content.
 */
abstract class MarketplaceNotification extends Notification
{
    use Queueable;

    abstract protected function title(): string;

    abstract protected function message(): string;

    abstract protected function url(object $notifiable): string;

    protected function icon(): string
    {
        return 'bi-bell';
    }

    protected function level(): string
    {
        return 'info';
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (setting('notify_mail_enabled') && filled($notifiable->email ?? null)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title(),
            'message' => $this->message(),
            'url' => $this->url($notifiable),
            'icon' => $this->icon(),
            'level' => $this->level(),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->title().' - '.setting('site_name'))
            ->greeting('Hello '.($notifiable->name ?? '').',')
            ->line($this->message())
            ->action('View details', $this->url($notifiable));
    }
}
