<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('search_queries', function (Blueprint $table) {
            $table->id();
            $table->string('term', 100);
            $table->unsignedInteger('count')->default(1);
            $table->timestamp('last_searched_at');
            $table->timestamps();

            $table->unique('term');
            $table->index(['count', 'last_searched_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_queries');
    }
};
