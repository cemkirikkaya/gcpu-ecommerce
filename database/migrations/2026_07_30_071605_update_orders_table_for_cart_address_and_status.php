<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('order_items')->delete();
        DB::table('orders')->delete();

        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');

            $table->foreignId('cart_id')->after('id')->constrained()->cascadeOnDelete();
            $table->foreignId('address_id')->nullable()->after('cart_id')->constrained()->nullOnDelete();
            $table->string('status')->default('pending')->after('total_price')->index();
            $table->string('payment_status')->default('pending')->after('status')->index();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropForeign(['address_id']);
            $table->dropColumn(['address_id', 'status', 'payment_status']);
            $table->dropForeign(['cart_id']);
            $table->dropColumn('cart_id');

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        });
    }
};
