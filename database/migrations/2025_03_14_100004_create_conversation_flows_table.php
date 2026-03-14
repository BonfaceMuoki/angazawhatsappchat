<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversation_flows', function (Blueprint $table) {
            $table->id();
            $table->string('stage')->unique();
            $table->text('question');
            $table->json('options')->nullable();
            $table->string('next_stage')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversation_flows');
    }
};
