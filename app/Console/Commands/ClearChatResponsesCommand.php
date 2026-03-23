<?php

namespace App\Console\Commands;

use App\Models\Message;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Clears WhatsApp chat message history and conversation sessions.
 */
class ClearChatResponsesCommand extends Command
{
    protected $signature = 'chat:clear-responses
                            {--force : Run without confirmation}
                            {--messages-only : Only truncate `messages`; keep `conversations` rows}
                            {--outgoing-only : Delete only outgoing (bot) messages; keep user messages}';

    protected $description = 'Clear chat data: by default truncates `messages` and `conversations`. Use --messages-only or --outgoing-only to narrow scope.';

    public function handle(): int
    {
        $scope = $this->option('outgoing-only')
            ? 'outgoing (bot) messages'.($this->option('messages-only') ? '' : ' and all conversation sessions')
            : 'all messages'.($this->option('messages-only') ? '' : ' and all conversation sessions');

        if (! $this->option('force') && ! $this->confirm("This will remove {$scope} from the database. Continue?", false)) {
            $this->warn('Aborted.');

            return self::FAILURE;
        }

        if ($this->option('outgoing-only')) {
            $deleted = DB::table('messages')->where('direction', Message::DIRECTION_OUTGOING)->delete();
            $this->info("Deleted {$deleted} outgoing message(s).");

            if (! $this->option('messages-only')) {
                $this->truncateConversationsTable();
            } else {
                $this->warn('Conversation rows kept (--messages-only).');
            }

            return self::SUCCESS;
        }

        $this->info('Truncating `messages`…');

        Schema::disableForeignKeyConstraints();
        try {
            DB::table('messages')->truncate();
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        $this->info('Truncated `messages`.');

        if (! $this->option('messages-only')) {
            $this->truncateConversationsTable();
        } else {
            $this->warn('Conversation rows kept (--messages-only).');
        }

        return self::SUCCESS;
    }

    protected function truncateConversationsTable(): void
    {
        $this->info('Truncating `conversations`…');
        Schema::disableForeignKeyConstraints();
        try {
            DB::table('conversations')->truncate();
        } finally {
            Schema::enableForeignKeyConstraints();
        }
        $this->info('Truncated `conversations`.');
    }
}
