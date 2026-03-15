<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bot_flows', function (Blueprint $table) {
            $table->foreign('entry_node_id')->references('id')->on('bot_nodes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('bot_flows', function (Blueprint $table) {
            $table->dropForeign(['entry_node_id']);
        });
    }
};
