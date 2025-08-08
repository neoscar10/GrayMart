<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;

class GenericNotification extends Notification
{
    use Queueable;

    public function __construct(public string $title, public string $body, public ?string $url = null) {}

    public function via($notifiable)
    {
        return ['database','broadcast','mail'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => $this->title,
            'body'  => $this->body,
            'url'   => $this->url,
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'data' => [
              'title' => $this->title,
              'body'  => $this->body,
              'url'   => $this->url,
            ]
        ]);
    }

    

public function toMail($notifiable)
{
    // Log for sanity
    \Log::info('Sending GenericNotification to: '.$notifiable->email);

    return (new MailMessage)
        ->from(config('mail.from.address'), config('mail.from.name'))
        ->subject($this->title)
        ->line($this->body)
        ->when($this->url, fn($mail) => $mail->action('View', url($this->url)));
}



}
