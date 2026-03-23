<?php

namespace Database\Seeders;

use App\Models\BotEdge;
use App\Models\BotFlow;
use App\Models\BotNode;
use Illuminate\Database\Seeder;

/**
 * Seeds the "Ongoing student" flow. Run after CurrentUserJourneySeeder.
 * Router shows "New student" | "Ongoing student" | "Staff"; choosing "Ongoing student" starts this flow.
 *
 * Usage: php artisan db:seed --class=OngoingStudentFlowSeeder
 */
class OngoingStudentFlowSeeder extends Seeder
{
    public function run(): void
    {
        $flow = BotFlow::updateOrCreate(
            ['name' => 'Ongoing student'],
            [
                'description' => 'Support for current IADL students — course help, resources, questions.',
                'entry_node_id' => null,
                'show_in_router' => true,
                'display_order' => 1,
                'is_active' => true,
            ]
        );

        $stages = [
            [
                'node_key' => 'welcome',
                'type' => 'list',
                'message' => "Hi 👋\nWelcome back. You're already on your IADL journey.\n\nWhat do you need help with?",
                'options' => [
                    ['value' => 'continue_course', 'label' => 'Continue my course', 'next' => 'continue_response'],
                    ['value' => 'assignment_help', 'label' => 'Assignment & project help', 'next' => 'assignment_help_response'],
                    ['value' => 'schedule', 'label' => 'Schedule or attendance', 'next' => 'schedule_response'],
                    ['value' => 'other', 'label' => 'Something else', 'next' => 'other_response'],
                ],
                'next' => null,
                'is_entry' => true,
            ],
            [
                'node_key' => 'continue_response',
                'type' => 'text',
                'message' => "You can pick up where you left off in your learning dashboard. If you need a reminder on deadlines or modules, check your cohort channel or email your facilitator.",
                'options' => [],
                'next' => null,
                'is_entry' => false,
            ],
            [
                'node_key' => 'assignment_help_response',
                'type' => 'text',
                'message' => "For assignment and project help, our team will follow up shortly. You can also ask in your cohort channel or join office hours — we're here to help you succeed.",
                'options' => [],
                'next' => null,
                'is_entry' => false,
            ],
            [
                'node_key' => 'schedule_response',
                'type' => 'text',
                'message' => "For schedule or attendance questions, we'll get back to you shortly. You can also check your cohort timetable or email support with your student ID.",
                'options' => [],
                'next' => null,
                'is_entry' => false,
            ],
            [
                'node_key' => 'other_response',
                'type' => 'text',
                'message' => "Thanks for reaching out. Our team will follow up with you shortly. You can also email support or ask in your cohort channel.",
                'options' => [],
                'next' => null,
                'is_entry' => false,
            ],
        ];

        $this->seedFlowStages($flow, $stages);

        $this->command->info('Ongoing student flow seeded.');
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
            if (!$sourceNode || empty($stage['options'])) {
                continue;
            }
            $order = 0;
            foreach ($stage['options'] as $opt) {
                $nextKey = $opt['next'] ?? $stage['next'] ?? null;
                if (!$nextKey) {
                    continue;
                }
                $targetNode = $nodesByKey[$nextKey] ?? null;
                if (!$targetNode) {
                    continue;
                }
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
