<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('search_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('query', 255);
            $table->timestamp('searched_at')->useCurrent();
            $table->timestamps();

            $table->index(['user_id', 'searched_at']);
            $table->unique(['user_id', 'query']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_histories');
    }
};

