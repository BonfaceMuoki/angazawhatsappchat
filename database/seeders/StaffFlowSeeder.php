<?php

namespace Database\Seeders;

use App\Models\BotEdge;
use App\Models\BotFlow;
use App\Models\BotNode;
use Illuminate\Database\Seeder;

/**
 * Seeds the "Staff" flow. Run after CurrentUserJourneySeeder and OngoingStudentFlowSeeder.
 * Router shows "New student" | "Ongoing student" | "Staff"; choosing "Staff" starts this flow.
 *
 * Usage: php artisan db:seed --class=StaffFlowSeeder
 */
class StaffFlowSeeder extends Seeder
{
    public function run(): void
    {
        $flow = BotFlow::updateOrCreate(
            ['name' => 'Staff'],
            [
                'description' => 'IADL staff — resources, support, and internal enquiries.',
                'entry_node_id' => null,
                'show_in_router' => true,
                'display_order' => 2,
                'is_active' => true,
            ]
        );

        $stages = [
            [
                'node_key' => 'welcome',
                'type' => 'list',
                'message' => "Hi 👋\nWelcome, staff member.\n\nHow can we help you today?",
                'options' => [
                    ['value' => 'resources', 'label' => 'Training / resources'],
                    ['value' => 'admin', 'label' => 'Admin / HR'],
                    ['value' => 'tech', 'label' => 'Tech support'],
                    ['value' => 'other', 'label' => 'Other'],
                ],
                'next' => 'staff_response',
                'is_entry' => true,
            ],
            [
                'node_key' => 'staff_response',
                'type' => 'text',
                'message' => "Thanks. The relevant team will get back to you shortly.",
                'options' => [],
                'next' => null,
                'is_entry' => false,
            ],
        ];

        $this->seedFlowStages($flow, $stages);

        $this->command->info('Staff flow seeded.');
    }

    protected function seedFlowStages(BotFlow $flow, array $stages): void
    {
        $nodesByKey = [];
        $positionY = 0;

        foreach ($stages as $stage) {
            $node = BotNode::updateOrCreate(
                ['flow_id' => $flow->id, 'node_key' => $stage['node_key']],
                [
                    'type' => $stage['type'],
                    'message' => $stage['message'],
                    'position_x' => 0,
                    'position_y' => $positionY,
                    'is_entry' => $stage['is_entry'],
                    'is_active' => true,
                ]
            );
            $nodesByKey[$stage['node_key']] = $node;
            $positionY += 150;
        }

        foreach ($stages as $stage) {
            $sourceNode = $nodesByKey[$stage['node_key']] ?? null;
            $nextKey = $stage['next'] ?? null;
            if (!$sourceNode || !$nextKey || empty($stage['options'])) {
                continue;
            }
            $targetNode = $nodesByKey[$nextKey] ?? null;
            if (!$targetNode) {
                continue;
            }
            $order = 0;
            foreach ($stage['options'] as $opt) {
                BotEdge::updateOrCreate(
                    [
                        'source_node_id' => $sourceNode->id,
                        'option_value' => $opt['value'],
                    ],
                    [
                        'target_node_id' => $targetNode->id,
                        'option_label' => $opt['label'],
                        'order' => $order++,
                    ]
                );
            }
        }

        $entryKey = null;
        foreach ($stages as $stage) {
            if (!empty($stage['is_entry'])) {
                $entryKey = $stage['node_key'];
                break;
            }
        }
        if ($entryKey && isset($nodesByKey[$entryKey])) {
            $flow->update(['entry_node_id' => $nodesByKey[$entryKey]->id]);
        }
    }
}
