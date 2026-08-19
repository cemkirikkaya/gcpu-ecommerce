<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('tracking_number')->nullable()->after('paid_at');
            $table->string('tracking_url')->nullable()->after('tracking_number');
            $table->string('geliver_shipment_id')->nullable()->after('tracking_url');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'tracking_number',
                'tracking_url',
                'geliver_shipment_id',
            ]);
        });
    }
};
