<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {

            $table->dropUnique(['cart_id', 'product_id']);

            $table->dropForeign(['product_id']);
            $table->dropColumn('product_id');

            $table->foreignId('product_variant_id')
                ->after('cart_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->unique([
                'cart_id',
                'product_variant_id',
            ]);

        });
    }

    public function down(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {

            $table->dropUnique([
                'cart_id',
                'product_variant_id',
            ]);

            $table->dropForeign(['product_variant_id']);
            $table->dropColumn('product_variant_id');

            $table->foreignId('product_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->unique([
                'cart_id',
                'product_id',
            ]);

        });
    }
};
