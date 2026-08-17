<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderShippedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order)
    {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Siparişiniz kargoya verildi — #{$this->order->id}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.orders.shipped',
            with: [
                'customerName' => $this->order->user()?->name ?? 'Müşterimiz',
                'orderUrl' => rtrim((string) config('app.frontend_url'), '/')."/orders/{$this->order->id}",
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
