<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Truncates bot graph tables (flows, nodes, edges). Safe for MySQL/SQLite/Postgres
 * when foreign keys are temporarily disabled; related rows on conversations/messages
 * are cleared first so FK constraints do not block truncation.
 */
class TruncateBotJourneyTablesCommand extends Command
{
    protected $signature = 'bot:truncate-journeys
                            {--force : Run without confirmation}';

    protected $description = 'Truncate bot_edges, bot_nodes, and bot_flows (removes all chatbot journeys). Nulls FK columns on messages and conversations first.';

    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm('This will remove ALL bot flows, nodes, and edges. Continue?', false)) {
            $this->warn('Aborted.');

            return self::FAILURE;
        }

        $this->info('Clearing foreign keys on messages and conversations…');

        DB::table('messages')->update(['node_id' => null]);
        DB::table('conversations')->update([
            'flow_id' => null,
            'current_node_id' => null,
        ]);

        $this->info('Truncating bot_edges → bot_nodes → bot_flows…');

        Schema::disableForeignKeyConstraints();

        try {
            DB::table('bot_edges')->truncate();
            DB::table('bot_nodes')->truncate();
            DB::table('bot_flows')->truncate();
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        $this->info('Done. Run `php artisan db:seed --class=IadlConciergeFlowSeeder` to restore the IADL flow.');

        return self::SUCCESS;
    }
}
