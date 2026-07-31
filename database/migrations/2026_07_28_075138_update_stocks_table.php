<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('stocks', 'product_variant_id')) {
            return;
        }

        Schema::table('stocks', function (Blueprint $table) {
            $table->dropForeign(['product_variant_value_id']);
            $table->dropColumn('product_variant_value_id');
        });

        Schema::table('stocks', function (Blueprint $table) {
            if (Schema::hasColumn('stocks', 'sku')) {
                $table->dropUnique(['sku']);
                $table->dropColumn('sku');
            }

            if (Schema::hasColumn('stocks', 'price')) {
                $table->dropColumn('price');
            }
        });

        Schema::table('stocks', function (Blueprint $table) {
            $table->foreignId('product_variant_id')
                ->constrained()
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('stocks', 'product_variant_id')) {
            return;
        }

        Schema::table('stocks', function (Blueprint $table) {
            $table->dropForeign(['product_variant_id']);
            $table->dropColumn('product_variant_id');
        });

        Schema::table('stocks', function (Blueprint $table) {
            $table->foreignId('product_variant_value_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('sku')->unique();
            $table->decimal('price', 10, 2);
        });
    }
};
