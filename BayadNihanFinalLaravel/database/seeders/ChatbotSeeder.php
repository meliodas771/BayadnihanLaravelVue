<?php

namespace Database\Seeders;

use App\Models\Chatbot;
use Illuminate\Database\Seeder;

class ChatbotSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $chatbots = [
            [
                'question' => 'What is BayadNihan?',
                'answer' => 'BayadNihan is a platform that connects students who need errands done with those who can do them. It allows students to post tasks, accept assignments, and earn money within their campus community.',
                'sort_order' => 1,
            ],
            [
               'question' => 'How do I create an account?',
                'answer' => 'To create an account, click on the "Register" button on the homepage. You can sign up as a Poster (someone who posts tasks), Doer (someone who completes tasks), or both. You\'ll need to verify your student email address.',
                'sort_order' => 2,
            ],
            [
                'question' => 'How do I post a task?',
                'answer' => 'After logging in, click on "Post a Task" from the dashboard. Fill in the task details including title, description, price, deadline, and any special instructions. Then publish your task.',
                'sort_order' => 3,
            ],
            [
                'question' => 'How do I find tasks to complete?',
                'answer' => 'Browse available tasks from the "Browse Tasks" section. You can filter tasks by category, price, or location. Click "Apply" on tasks that interest you.',
                'sort_order' => 4,
            ],
            [
                'question' => 'How does payment work?',
                'answer' => 'Payments are handled directly between students. We recommend using secure payment methods like bank transfers, GCash, or PayMaya. Always communicate payment details through our messaging system.',
                'sort_order' => 5,
            ],
            [
                'question' => 'Is my information safe?',
                'answer' => 'Yes, we take privacy seriously. All student accounts are verified, and we use secure encryption for all communications. Never share personal information outside of our platform.',
                'sort_order' => 6,
            ],
            [
                 'question' => 'What types of tasks can I post?',
                'answer' => 'You can post various student-friendly tasks such as grocery shopping, laundry, printing documents, library research, errand running, tutoring help, and other campus-related services.',
                'sort_order' => 7,
            ],
            [
                'question' => 'How do I contact the task poster/doer?',
                'answer' => 'Once a task is accepted, you can use our built-in messaging system to communicate with each other. Discuss details, clarify requirements, and coordinate meeting times.',
                'sort_order' => 8,
            ],
            [
                'question' => 'What if I have a problem with a task?',
                'answer' => 'If you encounter any issues, contact our support team through the contact information in the footer. You can also report concerns using the report feature in your dashboard.',
                'sort_order' => 9,
            ],
            [
                'question' => 'Can I be both a poster and doer?',
                'answer' => 'Yes! Many students choose to be both posters and doers. You can post tasks when you need help and accept tasks when you want to earn money.',
                'sort_order' => 10,
            ],
            [
                'question' => 'How do I leave feedback?',
                'answer' => 'After completing a task, both parties can leave feedback for each other. This helps build trust in our community and improves the platform for everyone.',
                'sort_order' => 11,
            ],
            [
                'question' => 'What are the platform fees?',
                'answer' => 'BayadNihan is free to use! There are no platform fees for posting tasks or completing assignments. We make money by connecting students in need.',
                'sort_order' => 12,
            ],
        ];

        foreach ($chatbots as $chatbot) {
            Chatbot::create($chatbot);
        }
    }
}
