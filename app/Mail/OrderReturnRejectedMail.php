<?php

namespace App\Mail;

use App\Models\OrderReturnRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderReturnRejectedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public OrderReturnRequest $returnRequest)
    {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        $label = $this->returnRequest->type->label();

        return new Envelope(
            subject: "{$label} talebiniz reddedildi — #{$this->returnRequest->order_id}",
        );
    }

    public function content(): Content
    {
        $order = $this->returnRequest->order;

        return new Content(
            markdown: 'mail.orders.return-rejected',
            with: [
                'customerName' => $this->returnRequest->user->name,
                'order' => $order,
                'returnRequest' => $this->returnRequest,
                'orderUrl' => rtrim((string) config('app.frontend_url'), '/')."/orders/{$order->id}",
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
