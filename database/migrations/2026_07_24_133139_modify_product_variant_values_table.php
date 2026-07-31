<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('product_variant_values', 'product_id')) {
            return;
        }

        Schema::table('product_variant_values', function (Blueprint $table) {
            $table->dropUnique(['product_id', 'variant_value_id']);
        });

        Schema::table('product_variant_values', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropColumn('product_id');
        });

        Schema::table('product_variant_values', function (Blueprint $table) {
            $table->foreignId('product_variant_id')
                ->after('id')
                ->constrained()
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('product_variant_values', 'product_variant_id')) {
            return;
        }

        Schema::table('product_variant_values', function (Blueprint $table) {
            $table->dropForeign(['product_variant_id']);
            $table->dropColumn('product_variant_id');
        });

        Schema::table('product_variant_values', function (Blueprint $table) {
            $table->foreignId('product_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->unique(['product_id', 'variant_value_id']);
        });
    }
};
