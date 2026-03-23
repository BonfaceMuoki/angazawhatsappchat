<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bot_edges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_node_id')->constrained('bot_nodes')->cascadeOnDelete();
            $table->foreignId('target_node_id')->constrained('bot_nodes')->cascadeOnDelete();
            $table->string('option_label');
            $table->string('option_value');
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_edges');
    }
};
