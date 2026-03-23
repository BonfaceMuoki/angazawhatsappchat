<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->foreignId('flow_id')->nullable()->after('stage')->constrained('bot_flows')->nullOnDelete();
            $table->foreignId('current_node_id')->nullable()->after('flow_id')->constrained('bot_nodes')->nullOnDelete();
            $table->boolean('bot_active')->default(true)->after('current_node_id');
            $table->unsignedBigInteger('assigned_agent_id')->nullable()->after('bot_active');
            $table->timestamp('human_intervened_at')->nullable()->after('assigned_agent_id');
            $table->timestamp('last_user_message_at')->nullable()->after('human_intervened_at');
            $table->timestamp('last_bot_message_at')->nullable()->after('last_user_message_at');
            $table->timestamp('last_human_message_at')->nullable()->after('last_bot_message_at');
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropForeign(['flow_id']);
            $table->dropForeign(['current_node_id']);
            $table->dropColumn([
                'flow_id', 'current_node_id', 'bot_active', 'assigned_agent_id',
                'human_intervened_at', 'last_user_message_at', 'last_bot_message_at', 'last_human_message_at',
            ]);
        });
    }
};
