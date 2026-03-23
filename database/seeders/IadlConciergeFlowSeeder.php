<?php

namespace Database\Seeders;

use App\Models\BotEdge;
use App\Models\BotFlow;
use App\Models\BotNode;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * IADL WhatsApp Concierge — full menu-led journey (see docs/IADL_WHATSAPP_JOURNEY.md).
 *
 * Non-developers: rebuild the same journey in the dashboard using docs/IADL_WHATSAPP_USER_GUIDE.md
 *
 * Usage: php artisan db:seed --class=IadlConciergeFlowSeeder
 *
 * Clears **all** rows in `bot_edges`, `bot_nodes`, and `bot_flows`, then seeds only this flow.
 * (Conversations keep `flow_id` / `current_node_id` as null per FK; messages may lose `node_id` links.)
 */
class IadlConciergeFlowSeeder extends Seeder
{
    public const FLOW_NAME = 'IADL Concierge';

    public function run(): void
    {
        $this->clearBotTables();

        $flow = BotFlow::create([
            'name' => self::FLOW_NAME,
            'description' => 'IADL guided admissions concierge — learn, programs, apply, info session, tuition, career, parent, partner, human.',
            'entry_node_id' => null,
            'show_in_router' => true,
            'display_order' => 0,
            'is_active' => true,
        ]);

        $nodesByKey = [];
        $y = 0;
        foreach ($this->nodeDefinitions() as $def) {
            $nodesByKey[$def['key']] = BotNode::create([
                'flow_id' => $flow->id,
                'node_key' => $def['key'],
                'type' => $def['type'],
                'message' => $def['message'],
                'position_x' => 0,
                'position_y' => $y,
                'is_entry' => $def['is_entry'] ?? false,
                'is_active' => true,
            ]);
            $y += 120;
        }

        foreach ($this->edgeDefinitions() as $edge) {
            BotEdge::create([
                'source_node_id' => $nodesByKey[$edge['from']]->id,
                'target_node_id' => $nodesByKey[$edge['to']]->id,
                'option_value' => $edge['value'],
                'option_label' => $edge['label'],
                'order' => $edge['order'] ?? 0,
            ]);
        }

        $flow->update(['entry_node_id' => $nodesByKey['entry']->id]);

        $edgeCount = count($this->edgeDefinitions());
        $this->command->info(self::FLOW_NAME.' seeded: '.count($nodesByKey)." nodes, {$edgeCount} edges.");
    }

    /**
     * Empty bot graph tables so only the IADL flow is seeded.
     * Order: null `entry_node_id` on flows → edges → nodes → flows (FK-safe).
     */
    protected function clearBotTables(): void
    {
        DB::transaction(function () {
            BotFlow::query()->update(['entry_node_id' => null]);
            BotEdge::query()->delete();
            BotNode::query()->delete();
            BotFlow::query()->delete();
        });
    }

    /**
     * @return array<int, array{key: string, type: string, message: string, is_entry?: bool}>
     */
    protected function nodeDefinitions(): array
    {
        $applyUrl = 'https://iadl.angazacenter.org/apply';

        return [
            [
                'key' => 'entry',
                'type' => 'list',
                'is_entry' => true,
                'message' => "Welcome to IADL by Angaza Center.\n\n"
                    ."We help learners build job-ready digital skills through hands-on training, portfolio projects, mentor support, and career guidance.\n\n"
                    .'Choose an option:',
            ],
            [
                'key' => 'branch_learn',
                'type' => 'buttons',
                'message' => "IADL is Angaza Center's skills accelerator — selective, cohort-based, project-first.\n\n"
                    ."We focus on job-relevant digital skills, mentorship, and responsible AI-first learning.\n\n"
                    .'Learners are often late high school grads, college students, or early-career professionals. We offer 4-week and 12-week paths.\n\n'
                    .'Tap *Continue* for next steps.',
            ],
            [
                'key' => 'learn_next',
                'type' => 'list',
                'message' => 'What would you like to do next?',
            ],
            [
                'key' => 'branch_explore',
                'type' => 'list',
                'message' => "Which option fits you best?\n\n"
                    .'• *4-week*: faster upskilling, tools, practical deliverables, lighter weekly load (about 8–12 hrs/week).'
                    ."\n• *12-week*: deeper skills, capstones, portfolio, career readiness (about 12–18 hrs/week).",
            ],
            [
                'key' => 'explore_4w',
                'type' => 'buttons',
                'message' => "*4-week Foundations / Skill Sprints* suit quick upskilling and tool mastery.\n\n"
                    ."Examples: Gemini / ChatGPT / Claude Mastery, Data Analytics & Visualization, Data Storytelling, Canva Mastery, AI Agents (design, deploy).\n\n"
                    .'Tap *Next* to choose apply, info session, or menu.',
            ],
            [
                'key' => 'explore_4w_next',
                'type' => 'buttons',
                'message' => 'Where should we take you next?',
            ],
            [
                'key' => 'explore_12w',
                'type' => 'buttons',
                'message' => "*12-week Accelerator / Career Tracks* suit deeper skills, capstones, and placement readiness.\n\n"
                    ."Examples: Software Engineering, Cloud Computing, Cyber Security, Robotics & IoT, Data Engineering, Data Science, AWS DevOps.\n\n"
                    .'Tap *Next* to continue.',
            ],
            [
                'key' => 'explore_12w_next',
                'type' => 'buttons',
                'message' => 'Where should we take you next?',
            ],
            [
                'key' => 'explore_help',
                'type' => 'list',
                'message' => "Quick check — pick the path that's closer to your goal:",
            ],
            [
                'key' => 'branch_apply',
                'type' => 'buttons',
                'message' => "*Application readiness*\n\n"
                    ."We'll confirm program, start timing, weekly commitment, laptop & internet, and comfort with a short screening task.\n\n"
                    .'Tap *Continue* to pick a track and see admissions steps.',
            ],
            [
                'key' => 'apply_readiness',
                'type' => 'buttons',
                'message' => 'Which track are you leaning toward?',
            ],
            [
                'key' => 'apply_steps',
                'type' => 'buttons',
                'message' => "*Admissions (high level)*\n\n"
                    ."• Online application\n"
                    ."• Motivation & goals\n"
                    ."• Academic integrity & responsible AI policy\n"
                    ."• Short diagnostic\n"
                    ."• Interview when needed\n"
                    ."• Selective admission by fit & commitment\n\n"
                    .'Tap *Continue* when you are ready to choose your next step.',
            ],
            [
                'key' => 'apply_route',
                'type' => 'list',
                'message' => "Application link (save this):\n{$applyUrl}\n\nHow would you like to proceed?",
            ],
            [
                'key' => 'branch_info',
                'type' => 'buttons',
                'message' => "*Info sessions*\n\n"
                    .'We run group info sessions (virtual / open house style) so you can ask questions across programs — not just one course. '
                    ."We'll share dates by message or link you to the calendar when available.\n\n"
                    .'Reply in this chat for 1:1 follow-up after you register.',
            ],
            [
                'key' => 'branch_tuition',
                'type' => 'list',
                'message' => 'Tuition, scholarships & payment — what do you need?',
            ],
            [
                'key' => 'tuition_programs',
                'type' => 'buttons',
                'message' => "*Tuition (indicative)*\n\n"
                    ."• 4-week track: KES 35,000 (typically paid in full)\n"
                    ."• 12-week track: KES 75,000 (installments may be possible case-by-case)\n\n"
                    .'Scholarships are limited and based on need and merit.',
            ],
            [
                'key' => 'tuition_plans',
                'type' => 'buttons',
                'message' => "*Payment plans*\n\n"
                    .'4-week is generally paid in full. 12-week may allow case-by-case installment plans — admissions can confirm eligibility.',
            ],
            [
                'key' => 'tuition_scholarships',
                'type' => 'buttons',
                'message' => "*Scholarships*\n\n"
                    .'Scholarships are limited. We consider need and merit; details are shared during admissions and info sessions.',
            ],
            [
                'key' => 'tuition_includes',
                'type' => 'buttons',
                'message' => "*What tuition supports*\n\n"
                    .'Instruction, projects, mentor touchpoints, learning platform access, and career-readiness support aligned to your program (see program page for specifics).',
            ],
            [
                'key' => 'tuition_cta',
                'type' => 'buttons',
                'message' => 'Would you like to book an info session or talk to admissions?',
            ],
            [
                'key' => 'branch_career',
                'type' => 'buttons',
                'message' => "*Career outcomes (realistic)*\n\n"
                    ."• Portfolio-grade projects & evidence\n"
                    ."• CV, LinkedIn, portfolio, interview & mock support\n"
                    ."• Many active seekers aim for roles in ~3–6 months — *we do not guarantee employment*\n\n"
                    .'Tap *Next* to choose info session, apply, or menu.',
            ],
            [
                'key' => 'career_next',
                'type' => 'list',
                'message' => 'Pick a next step:',
            ],
            [
                'key' => 'branch_parent',
                'type' => 'list',
                'message' => 'Parent / guardian — what would you like to review?',
            ],
            [
                'key' => 'parent_overview',
                'type' => 'buttons',
                'message' => "*Program overview*\n\n"
                    .'IADL is hands-on and selective: cohorts, mentors, responsible AI use, and academic integrity. '
                    .'We emphasize practical skills and portfolio evidence over hype.',
            ],
            [
                'key' => 'parent_admissions',
                'type' => 'buttons',
                'message' => "*Admissions & selectivity*\n\n"
                    .'Admission considers fit, commitment, and readiness (including device & connectivity). '
                    .'There is a short diagnostic and sometimes an interview.',
            ],
            [
                'key' => 'parent_tuition',
                'type' => 'buttons',
                'message' => "*Tuition & scholarships*\n\n"
                    .'See indicative amounts in the Tuition branch (4-week vs 12-week). Scholarships are limited; we can discuss need/merit on a call.',
            ],
            [
                'key' => 'parent_schedule',
                'type' => 'buttons',
                'message' => "*Schedule & format*\n\n"
                    .'Expect guided live/structured work plus assignments — typically 8–12 hrs/week (4-week) or 12–18 hrs/week (12-week), depending on track.',
            ],
            [
                'key' => 'parent_outcomes',
                'type' => 'buttons',
                'message' => "*Support & outcomes*\n\n"
                    .'We support CV, LinkedIn, portfolio, and interviews. We prepare learners for entry-level roles or internships where relevant — without promising a job.',
            ],
            [
                'key' => 'branch_partner',
                'type' => 'list',
                'message' => 'Partnerships — what brings you here?',
            ],
            [
                'key' => 'partner_escalate',
                'type' => 'buttons',
                'message' => "*Thank you*\n\n"
                    .'Please share your *name, organization, email, and goal* in your next message so an Angaza / IADL partner lead can reply. '
                    .'Weekday response is typical; urgent items are triaged.',
            ],
            [
                'key' => 'branch_human',
                'type' => 'text',
                'message' => "*Admissions handoff*\n\n"
                    .'An admissions teammate will read this thread and reply here. '
                    .'If this is urgent, mention *URGENT* and your timezone.\n\n'
                    .'You can also email admissions with the same details.',
            ],
        ];
    }

    /**
     * @return array<int, array{from: string, to: string, value: string, label: string, order?: int}>
     */
    protected function edgeDefinitions(): array
    {
        $o = 0;

        return array_merge(
            // entry (main menu)
            [
                ['from' => 'entry', 'to' => 'branch_learn', 'value' => '1', 'label' => 'Learn about IADL', 'order' => $o++],
                ['from' => 'entry', 'to' => 'branch_explore', 'value' => '2', 'label' => 'Explore programs', 'order' => $o++],
                ['from' => 'entry', 'to' => 'branch_apply', 'value' => '3', 'label' => 'Apply now', 'order' => $o++],
                ['from' => 'entry', 'to' => 'branch_info', 'value' => '4', 'label' => 'Book info session', 'order' => $o++],
                ['from' => 'entry', 'to' => 'branch_tuition', 'value' => '5', 'label' => 'Tuition & scholarships', 'order' => $o++],
                ['from' => 'entry', 'to' => 'branch_career', 'value' => '6', 'label' => 'Career outcomes', 'order' => $o++],
                ['from' => 'entry', 'to' => 'branch_parent', 'value' => '7', 'label' => 'Parent / guardian', 'order' => $o++],
                ['from' => 'entry', 'to' => 'branch_partner', 'value' => '8', 'label' => 'Partnerships', 'order' => $o++],
                ['from' => 'entry', 'to' => 'branch_human', 'value' => '9', 'label' => 'Talk to a person', 'order' => $o++],
            ],
            // learn branch
            [
                ['from' => 'branch_learn', 'to' => 'learn_next', 'value' => 'continue', 'label' => 'Continue', 'order' => 0],
            ],
            [
                ['from' => 'learn_next', 'to' => 'branch_explore', 'value' => 'explore', 'label' => 'Explore programs', 'order' => 0],
                ['from' => 'learn_next', 'to' => 'apply_steps', 'value' => 'admissions', 'label' => 'See admissions steps', 'order' => 1],
                ['from' => 'learn_next', 'to' => 'branch_info', 'value' => 'info', 'label' => 'Book info session', 'order' => 2],
                ['from' => 'learn_next', 'to' => 'branch_human', 'value' => 'human', 'label' => 'Talk to a person', 'order' => 3],
            ],
            // explore
            [
                ['from' => 'branch_explore', 'to' => 'explore_4w', 'value' => '4w', 'label' => '4-week path', 'order' => 0],
                ['from' => 'branch_explore', 'to' => 'explore_12w', 'value' => '12w', 'label' => '12-week path', 'order' => 1],
                ['from' => 'branch_explore', 'to' => 'explore_help', 'value' => 'help', 'label' => 'Help me choose', 'order' => 2],
                ['from' => 'branch_explore', 'to' => 'entry', 'value' => 'menu', 'label' => 'Main menu', 'order' => 3],
            ],
            [
                ['from' => 'explore_4w', 'to' => 'explore_4w_next', 'value' => 'next', 'label' => 'Next', 'order' => 0],
            ],
            [
                ['from' => 'explore_4w_next', 'to' => 'branch_apply', 'value' => 'apply', 'label' => 'Apply now', 'order' => 0],
                ['from' => 'explore_4w_next', 'to' => 'branch_info', 'value' => 'info', 'label' => 'Info session', 'order' => 1],
                ['from' => 'explore_4w_next', 'to' => 'entry', 'value' => 'menu', 'label' => 'Main menu', 'order' => 2],
            ],
            [
                ['from' => 'explore_12w', 'to' => 'explore_12w_next', 'value' => 'next', 'label' => 'Next', 'order' => 0],
            ],
            [
                ['from' => 'explore_12w_next', 'to' => 'branch_apply', 'value' => 'apply', 'label' => 'Apply now', 'order' => 0],
                ['from' => 'explore_12w_next', 'to' => 'branch_info', 'value' => 'info', 'label' => 'Info session', 'order' => 1],
                ['from' => 'explore_12w_next', 'to' => 'entry', 'value' => 'menu', 'label' => 'Main menu', 'order' => 2],
            ],
            [
                ['from' => 'explore_help', 'to' => 'explore_4w', 'value' => '4w', 'label' => 'Choose 4-week sprint', 'order' => 0],
                ['from' => 'explore_help', 'to' => 'explore_12w', 'value' => '12w', 'label' => 'Choose 12-week track', 'order' => 1],
                ['from' => 'explore_help', 'to' => 'branch_human', 'value' => 'human', 'label' => 'Ask admissions', 'order' => 2],
            ],
            // apply
            [
                ['from' => 'branch_apply', 'to' => 'apply_readiness', 'value' => 'continue', 'label' => 'Continue', 'order' => 0],
            ],
            [
                ['from' => 'apply_readiness', 'to' => 'apply_steps', 'value' => 'path_4w', 'label' => '4-week track', 'order' => 0],
                ['from' => 'apply_readiness', 'to' => 'apply_steps', 'value' => 'path_12w', 'label' => '12-week track', 'order' => 1],
                ['from' => 'apply_readiness', 'to' => 'branch_explore', 'value' => 'not_sure', 'label' => 'Not sure yet', 'order' => 2],
            ],
            [
                ['from' => 'apply_steps', 'to' => 'apply_route', 'value' => 'continue', 'label' => 'Continue', 'order' => 0],
            ],
            [
                ['from' => 'apply_route', 'to' => 'entry', 'value' => 'ready', 'label' => 'Back to main menu', 'order' => 0],
                ['from' => 'apply_route', 'to' => 'branch_info', 'value' => 'support', 'label' => 'Brochure + info session', 'order' => 1],
                ['from' => 'apply_route', 'to' => 'branch_human', 'value' => 'human', 'label' => 'Talk to admissions', 'order' => 2],
            ],
            // info
            [
                ['from' => 'branch_info', 'to' => 'entry', 'value' => 'menu', 'label' => 'Main menu', 'order' => 0],
                ['from' => 'branch_info', 'to' => 'branch_apply', 'value' => 'apply', 'label' => 'Apply now', 'order' => 1],
            ],
            // tuition
            [
                ['from' => 'branch_tuition', 'to' => 'tuition_programs', 'value' => 'by_program', 'label' => 'Tuition by program', 'order' => 0],
                ['from' => 'branch_tuition', 'to' => 'tuition_plans', 'value' => 'plans', 'label' => 'Payment plans', 'order' => 1],
                ['from' => 'branch_tuition', 'to' => 'tuition_scholarships', 'value' => 'scholarships', 'label' => 'Scholarships', 'order' => 2],
                ['from' => 'branch_tuition', 'to' => 'tuition_includes', 'value' => 'includes', 'label' => 'What tuition includes', 'order' => 3],
                ['from' => 'branch_tuition', 'to' => 'branch_human', 'value' => 'human', 'label' => 'Talk to a person', 'order' => 4],
            ],
            [
                ['from' => 'tuition_programs', 'to' => 'tuition_cta', 'value' => 'next', 'label' => 'Continue', 'order' => 0],
                ['from' => 'tuition_plans', 'to' => 'tuition_cta', 'value' => 'next', 'label' => 'Continue', 'order' => 0],
                ['from' => 'tuition_scholarships', 'to' => 'tuition_cta', 'value' => 'next', 'label' => 'Continue', 'order' => 0],
                ['from' => 'tuition_includes', 'to' => 'tuition_cta', 'value' => 'next', 'label' => 'Continue', 'order' => 0],
            ],
            [
                ['from' => 'tuition_cta', 'to' => 'branch_info', 'value' => 'info', 'label' => 'Book info session', 'order' => 0],
                ['from' => 'tuition_cta', 'to' => 'branch_human', 'value' => 'human', 'label' => 'Talk to admissions', 'order' => 1],
            ],
            // career
            [
                ['from' => 'branch_career', 'to' => 'career_next', 'value' => 'next', 'label' => 'Next', 'order' => 0],
            ],
            [
                ['from' => 'career_next', 'to' => 'branch_info', 'value' => 'info', 'label' => 'Book info session', 'order' => 0],
                ['from' => 'career_next', 'to' => 'branch_apply', 'value' => 'apply', 'label' => 'Apply now', 'order' => 1],
                ['from' => 'career_next', 'to' => 'entry', 'value' => 'menu', 'label' => 'Main menu', 'order' => 2],
            ],
            // parent
            [
                ['from' => 'branch_parent', 'to' => 'parent_overview', 'value' => 'overview', 'label' => 'Program overview', 'order' => 0],
                ['from' => 'branch_parent', 'to' => 'parent_admissions', 'value' => 'admissions', 'label' => 'Admissions', 'order' => 1],
                ['from' => 'branch_parent', 'to' => 'parent_tuition', 'value' => 'tuition', 'label' => 'Tuition', 'order' => 2],
                ['from' => 'branch_parent', 'to' => 'parent_schedule', 'value' => 'schedule', 'label' => 'Schedule & format', 'order' => 3],
                ['from' => 'branch_parent', 'to' => 'parent_outcomes', 'value' => 'outcomes', 'label' => 'Career support', 'order' => 4],
                ['from' => 'branch_parent', 'to' => 'branch_human', 'value' => 'human', 'label' => 'Talk to admissions', 'order' => 5],
            ],
            $this->parentChildEdges('parent_overview'),
            $this->parentChildEdges('parent_admissions'),
            $this->parentChildEdges('parent_tuition'),
            $this->parentChildEdges('parent_schedule'),
            $this->parentChildEdges('parent_outcomes'),
            // partner
            [
                ['from' => 'branch_partner', 'to' => 'partner_escalate', 'value' => 'sponsor', 'label' => 'Scholarship sponsor', 'order' => 0],
                ['from' => 'branch_partner', 'to' => 'partner_escalate', 'value' => 'institutional', 'label' => 'Institutional partner', 'order' => 1],
                ['from' => 'branch_partner', 'to' => 'partner_escalate', 'value' => 'talent', 'label' => 'Talent / internships', 'order' => 2],
                ['from' => 'branch_partner', 'to' => 'partner_escalate', 'value' => 'media', 'label' => 'Media / speaking', 'order' => 3],
                ['from' => 'branch_partner', 'to' => 'branch_human', 'value' => 'human', 'label' => 'Talk to a person', 'order' => 4],
            ],
            [
                ['from' => 'partner_escalate', 'to' => 'entry', 'value' => 'menu', 'label' => 'Main menu', 'order' => 0],
                ['from' => 'partner_escalate', 'to' => 'branch_human', 'value' => 'human', 'label' => 'Talk to a person', 'order' => 1],
            ],
        );
    }

    /**
     * @return array<int, array{from: string, to: string, value: string, label: string, order: int}>
     */
    protected function parentChildEdges(string $from): array
    {
        return [
            ['from' => $from, 'to' => 'branch_parent', 'value' => 'back', 'label' => 'Parent menu', 'order' => 0],
            ['from' => $from, 'to' => 'branch_human', 'value' => 'human', 'label' => 'Talk to admissions', 'order' => 1],
            ['from' => $from, 'to' => 'entry', 'value' => 'menu', 'label' => 'Main menu', 'order' => 2],
        ];
    }
}
