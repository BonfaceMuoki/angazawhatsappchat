<?php

namespace Database\Seeders;

use App\Models\ConversationFlow;
use Illuminate\Database\Seeder;

class ConversationFlowSeeder extends Seeder
{
    public function run(): void
    {
        $flows = [
            [
                'stage' => 'entry',
                'question' => "Hi 👋\nWelcome to the Institute of Applied Digital Literacy (IADL).\n\nWe offer structured digital skills programs in:\n\n1️⃣ Software Engineering\n2️⃣ Data & Analytics\n3️⃣ Cloud Computing\n4️⃣ Cybersecurity\n\nChoose your role:",
                'options' => [
                    ['id' => 'student', 'title' => 'Student'],
                    ['id' => 'graduate', 'title' => 'Graduate'],
                    ['id' => 'professional', 'title' => 'Working professional'],
                    ['id' => 'parent', 'title' => 'Parent'],
                ],
                'next_stage' => 'role',
            ],
            [
                'stage' => 'role',
                'question' => "Which program are you most interested in?",
                'options' => [
                    ['id' => 'software_engineering', 'title' => 'Software Engineering'],
                    ['id' => 'data_analytics', 'title' => 'Data & Analytics'],
                    ['id' => 'cloud_computing', 'title' => 'Cloud Computing'],
                    ['id' => 'cybersecurity', 'title' => 'Cybersecurity'],
                    ['id' => 'not_sure', 'title' => 'Not sure yet'],
                ],
                'next_stage' => 'commitment',
            ],
            [
                'stage' => 'commitment',
                'question' => "How committed are you to learning over the next 3 months?",
                'options' => [
                    ['id' => 'commit_high', 'title' => '12–18 hrs/week'],
                    ['id' => 'commit_medium', 'title' => '8–12 hrs/week'],
                    ['id' => 'exploring', 'title' => 'Exploring options'],
                ],
                'next_stage' => 'experience',
            ],
            [
                'stage' => 'experience',
                'question' => "Have you done any of the following before?",
                'options' => [
                    ['id' => 'programming', 'title' => 'Programming'],
                    ['id' => 'data_analysis', 'title' => 'Data analysis'],
                    ['id' => 'cloud_platforms', 'title' => 'Cloud platforms'],
                    ['id' => 'none', 'title' => 'None'],
                ],
                'next_stage' => 'pricing',
            ],
            [
                'stage' => 'pricing',
                'question' => "4-Week Foundations: KES 35,000\n12-Week Career Accelerator: KES 75,000\n\nIs this within your budget?",
                'options' => [
                    ['id' => 'yes', 'title' => 'Yes'],
                    ['id' => 'installment', 'title' => 'Installment'],
                    ['id' => 'not_currently', 'title' => 'Not currently'],
                ],
                'next_stage' => 'education',
            ],
            [
                'stage' => 'education',
                'question' => "All learners must:\n\n• Maintain 80% attendance\n• Complete assignments\n• Build real projects\n• Present a final capstone\n\nAre you comfortable with this?",
                'options' => [
                    ['id' => 'yes', 'title' => 'Yes'],
                    ['id' => 'tell_me_more', 'title' => 'Tell me more'],
                ],
                'next_stage' => 'conversion',
            ],
            [
                'stage' => 'conversion',
                'question' => "Would you like to:",
                'options' => [
                    ['id' => 'apply_now', 'title' => 'Apply Now'],
                    ['id' => 'attend_info_session', 'title' => 'Attend Info Session'],
                ],
                'next_stage' => 'complete',
            ],
            [
                'stage' => 'complete',
                'question' => "Thank you for your interest in IADL. We'll be in touch shortly.",
                'options' => null,
                'next_stage' => null,
            ],
        ];

        foreach ($flows as $flow) {
            ConversationFlow::updateOrCreate(
                ['stage' => $flow['stage']],
                $flow
            );
        }
    }
}
