<?php

namespace App\Mail;

use App\Models\OrderCancellationRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderCancellationRejectedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public OrderCancellationRequest $cancellationRequest)
    {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "İptal talebiniz reddedildi — #{$this->cancellationRequest->order_id}",
        );
    }

    public function content(): Content
    {
        $order = $this->cancellationRequest->order;

        return new Content(
            markdown: 'mail.orders.cancellation-rejected',
            with: [
                'customerName' => $this->cancellationRequest->user->name,
                'order' => $order,
                'orderUrl' => rtrim((string) config('app.frontend_url'), '/')."/orders/{$order->id}",
                'adminNote' => $this->cancellationRequest->admin_note,
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
