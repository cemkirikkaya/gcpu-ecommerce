<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('variants', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('variant_values', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('product_variant_values', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('stocks', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('images', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('variants', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('variant_values', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('product_variant_values', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('stocks', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('images', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
