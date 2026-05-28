<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class NearExpiryMedicineAlert extends Notification
{
    use Queueable;

    public function __construct(
        protected Collection $batches,
        protected int $months = 6
    ) {}

    /**
     * Delivery channels — mail only for now.
     * Add 'database' here in the future to store in-app notifications.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $count = $this->batches->count();

        $message = (new MailMessage)
            ->subject("⚠️ {$count} Medicine Batch" . ($count > 1 ? 'es' : '') . " Expiring Within {$this->months} Months")
            ->greeting("Hello {$notifiable->name},")
            ->line(
                "The following medicine " . ($count > 1 ? 'batches are' : 'batch is') .
                " expiring within {$this->months} months and still " .
                ($count > 1 ? 'have' : 'has') . " remaining stock. " .
                "Please arrange for return to the supplier."
            );

        // List up to 20 batches inline — avoids excessively long emails
        foreach ($this->batches->take(20) as $batch) {
            $medicineName = $batch->medicine?->name ?? 'Unknown Medicine';
            $expiryDate   = $batch->expiry_date?->format('d M Y') ?? 'N/A';
            $daysLeft     = $batch->expiry_date ? now()->diffInDays($batch->expiry_date) : '?';

            $message->line(
                "• {$medicineName} | Batch: {$batch->batch_no} | " .
                "Expiry: {$expiryDate} ({$daysLeft} days) | " .
                "Remaining: {$batch->remaining_quantity} units"
            );
        }

        if ($count > 20) {
            $message->line('... and ' . ($count - 20) . ' more batches not shown here.');
        }

        return $message
            ->action('View Expiring Stock', url('/inventory/expiring'))
            ->line('Please take action before these medicines expire to avoid waste and compliance issues.');
    }

    /**
     * Array representation — used if 'database' channel is added later.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'batch_count' => $this->batches->count(),
            'months'      => $this->months,
        ];
    }
}
