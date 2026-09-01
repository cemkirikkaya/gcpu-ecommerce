<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_return_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type')->default('return');
            $table->string('status')->default('pending')->index();
            $table->text('message');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('admin_note')->nullable();
            $table->string('refund_reference')->nullable();
            $table->decimal('refund_amount', 10, 2)->nullable();
            $table->string('geliver_return_shipment_id')->nullable()->index();
            $table->string('return_tracking_number')->nullable();
            $table->string('return_tracking_url')->nullable();
            $table->string('return_label_url')->nullable();
            $table->string('geliver_exchange_shipment_id')->nullable();
            $table->string('exchange_tracking_number')->nullable();
            $table->string('exchange_tracking_url')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['order_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_return_requests');
    }
};
