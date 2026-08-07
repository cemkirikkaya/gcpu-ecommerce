<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            if (Schema::hasColumn('orders', 'payment_provider')) {
                $table->dropColumn([
                    'payment_provider',
                    'payment_reference',
                    'payment_token',
                ]);
            }

            $table->string('stripe_checkout_session_id')->nullable()->after('iyzico_conversation_id');
            $table->string('stripe_payment_intent_id')->nullable()->after('stripe_checkout_session_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn([
                'stripe_checkout_session_id',
                'stripe_payment_intent_id',
            ]);

            $table->string('payment_provider')->nullable()->after('payment_status');
            $table->string('payment_reference')->nullable()->after('payment_provider');
            $table->string('payment_token')->nullable()->after('payment_reference');
        });
    }
};
