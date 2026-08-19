<?php

namespace App\Mail;

use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BackInStockMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public ProductVariant $variant,
    ) {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        $productName = $this->variant->product?->name ?? 'Ürün';

        return new Envelope(
            subject: "Stoğa döndü — {$productName}",
        );
    }

    public function content(): Content
    {
        $this->variant->loadMissing('product');

        return new Content(
            markdown: 'mail.inventory.back-in-stock',
            with: [
                'customerName' => $this->user->name,
                'productName' => $this->variant->product?->name ?? 'Ürün',
                'variantLabel' => $this->variant->displayLabel() ?: $this->variant->sku,
                'productUrl' => rtrim((string) config('app.frontend_url'), '/').'/products/'.$this->variant->product_id,
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
