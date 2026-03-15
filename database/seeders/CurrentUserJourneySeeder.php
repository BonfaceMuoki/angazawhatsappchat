<?php

namespace Database\Seeders;

use App\Models\BotEdge;
use App\Models\BotFlow;
use App\Models\BotNode;
use App\Models\BotSetting;
use Illuminate\Database\Seeder;

/**
 * Seeds the "New student" flow (Admissions journey): program interest → commitment → … → complete.
 * Router choice "New student" is matched by flow name. Run first; then OngoingStudentFlowSeeder and StaffFlowSeeder.
 *
 * Usage: php artisan db:seed --class=CurrentUserJourneySeeder
 */
class CurrentUserJourneySeeder extends Seeder
{
    public function run(): void
    {
        // Deactivate old "Admissions" flow so router shows only New student / Ongoing student / Staff
        BotFlow::where('name', 'Admissions')->update(['is_active' => false]);

        $flow = BotFlow::updateOrCreate(
            ['name' => 'New student'],
            [
                'description' => 'New student admissions — program interest, commitment, experience, pricing, education, conversion.',
                'entry_node_id' => null,
                'show_in_router' => true,
                'display_order' => 0,
                'is_active' => true,
            ]
        );

        // Remove old "entry" (role choice) node if it existed; entry is now "role" (program interest)
        BotNode::where('flow_id', $flow->id)->where('node_key', 'entry')->get()->each(function (BotNode $n) {
            BotEdge::where('source_node_id', $n->id)->orWhere('target_node_id', $n->id)->delete();
            $n->delete();
        });

        $stages = [
            [
                'node_key' => 'role',
                'type' => 'list',
                'message' => "Which program are you most interested in?",
                'options' => [
                    ['value' => 'software_engineering', 'label' => 'Software Engineering'],
                    ['value' => 'data_analytics', 'label' => 'Data & Analytics'],
                    ['value' => 'cloud_computing', 'label' => 'Cloud Computing'],
                    ['value' => 'cybersecurity', 'label' => 'Cybersecurity'],
                    ['value' => 'not_sure', 'label' => 'Not sure yet'],
                ],
                'next' => 'commitment',
                'is_entry' => true,
            ],
            [
                'node_key' => 'commitment',
                'type' => 'buttons',
                'message' => "How committed are you to learning over the next 3 months?",
                'options' => [
                    ['value' => 'commit_high', 'label' => '12–18 hrs/week'],
                    ['value' => 'commit_medium', 'label' => '8–12 hrs/week'],
                    ['value' => 'exploring', 'label' => 'Exploring options'],
                ],
                'next' => 'experience',
                'is_entry' => false,
            ],
            [
                'node_key' => 'experience',
                'type' => 'list',
                'message' => "Have you done any of the following before?",
                'options' => [
                    ['value' => 'programming', 'label' => 'Programming'],
                    ['value' => 'data_analysis', 'label' => 'Data analysis'],
                    ['value' => 'cloud_platforms', 'label' => 'Cloud platforms'],
                    ['value' => 'none', 'label' => 'None'],
                ],
                'next' => 'pricing',
                'is_entry' => false,
            ],
            [
                'node_key' => 'pricing',
                'type' => 'buttons',
                'message' => "4-Week Foundations: KES 35,000\n12-Week Career Accelerator: KES 75,000\n\nIs this within your budget?",
                'options' => [
                    ['value' => 'yes', 'label' => 'Yes'],
                    ['value' => 'installment', 'label' => 'Installment'],
                    ['value' => 'not_currently', 'label' => 'Not currently'],
                ],
                'next' => 'education',
                'is_entry' => false,
            ],
            [
                'node_key' => 'education',
                'type' => 'buttons',
                'message' => "All learners must:\n\n• Maintain 80% attendance\n• Complete assignments\n• Build real projects\n• Present a final capstone\n\nAre you comfortable with this?",
                'options' => [
                    ['value' => 'yes', 'label' => 'Yes'],
                    ['value' => 'tell_me_more', 'label' => 'Tell me more'],
                ],
                'next' => 'conversion',
                'is_entry' => false,
            ],
            [
                'node_key' => 'conversion',
                'type' => 'buttons',
                'message' => "Would you like to:",
                'options' => [
                    ['value' => 'apply_now', 'label' => 'Apply Now'],
                    ['value' => 'attend_info_session', 'label' => 'Attend Info Session'],
                ],
                'next' => 'complete',
                'is_entry' => false,
            ],
            [
                'node_key' => 'complete',
                'type' => 'text',
                'message' => "Thank you for your interest in IADL. We'll be in touch shortly.",
                'options' => [],
                'next' => null,
                'is_entry' => false,
            ],
        ];

        $this->seedFlowStages($flow, $stages);
        BotSetting::setValue('ai_enabled', '1');
        BotSetting::setValue('ai_mode', 'intent_detection');

        $this->command->info('New student (Admissions) flow seeded: ' . count($stages) . ' nodes.');
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
