<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bot_nodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flow_id')->constrained('bot_flows')->cascadeOnDelete();
            $table->string('node_key');
            $table->string('type', 20)->default('text'); // text, buttons, list
            $table->text('message');
            $table->decimal('position_x', 10, 2)->default(0);
            $table->decimal('position_y', 10, 2)->default(0);
            $table->boolean('is_entry')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['flow_id', 'node_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_nodes');
    }
};
