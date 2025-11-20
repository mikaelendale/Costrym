<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * SubscriptionStatusChanged Notification
 *
 * Notifies users when their subscription status changes, including:
 * - Plan swaps
 * - Subscription pauses/resumes
 * - Cancellations
 * - Payment method updates
 */
class SubscriptionStatusChanged extends Notification
{
    use Queueable;

    /**
     * The message to display in the notification.
     */
    private string $message;

    /**
     * Create a new notification instance.
     *
     * @param  string  $message  The notification message describing the subscription change
     */
    public function __construct(string $message)
    {
        $this->message = $message;
    }

    /**
     * Get the notification's delivery channels.
     *
     * Currently configured to deliver via database only.
     * Can be extended to include email, SMS, etc.
     *
     * @param  object  $notifiable  The user being notified
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * Optional: Can be implemented to send email notifications
     * for subscription changes.
     *
     * @param  object  $notifiable  The user being notified
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Subscription Status Update')
            ->line($this->message)
            ->action('View Subscription', url('/dashboard'))
            ->line('Thank you for using our service!');
    }

    /**
     * Get the array representation of the notification.
     *
     * This format is stored in the database and displayed in the
     * user's notification center.
     *
     * @param  object  $notifiable  The user being notified
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'icon' => '💳',
            'message' => $this->message,
            'description' => 'Your subscription status has been updated.',
            'action' => 'View Dashboard',
            'url' => url('/dashboard'),
        ];
    }
}
