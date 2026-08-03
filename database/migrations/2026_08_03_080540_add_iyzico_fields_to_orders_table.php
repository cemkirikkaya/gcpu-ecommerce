<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->string('iyzico_token')->nullable()->after('payment_status');
            $table->string('iyzico_payment_id')->nullable()->after('iyzico_token');
            $table->string('iyzico_conversation_id')->nullable()->after('iyzico_payment_id');
            $table->timestamp('paid_at')->nullable()->after('iyzico_conversation_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn([
                'iyzico_token',
                'iyzico_payment_id',
                'iyzico_conversation_id',
                'paid_at',
            ]);
        });
    }
};
