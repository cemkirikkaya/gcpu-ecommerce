<?php

namespace App\Mail;

use App\Models\ProductVariant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LowStockMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public ProductVariant $variant,
        public int $quantity,
        public int $threshold,
    ) {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        $productName = $this->variant->product?->name ?? 'Ürün';

        return new Envelope(
            subject: "Düşük stok uyarısı — {$productName}",
        );
    }

    public function content(): Content
    {
        $this->variant->loadMissing('product.vendor');

        return new Content(
            markdown: 'mail.inventory.low-stock',
            with: [
                'vendorName' => $this->variant->product?->vendor?->name ?? 'Satıcı',
                'productName' => $this->variant->product?->name ?? 'Ürün',
                'sku' => $this->variant->sku,
                'quantity' => $this->quantity,
                'threshold' => $this->threshold,
                'productsUrl' => rtrim((string) config('app.frontend_url'), '/').'/admin/products',
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
