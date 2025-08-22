<?php

namespace Database\Seeders;

use App\Models\Question;
use App\Models\Option;
use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class QuestionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure we have categories to work with
        $categories = Category::all();

        if ($categories->isEmpty()) {
            $this->command->warn('No categories found. Please run CategorySeeder first.');
            return;
        }

        $this->command->info('Creating sample questions with options...');

        // Sample questions data organized by category
        $questionsData = [
            'Computer Science & IT' => [
                [
                    'question_text' => 'What is the primary purpose of HTML?',
                    'options' => [
                        'To style web pages',
                        'To structure and organize web content',
                        'To add interactivity to websites',
                        'To manage databases'
                    ],
                    'correct_option_index' => 1,
                    'points' => 2
                ],
                [
                    'question_text' => 'Which programming language is known as the "language of the web"?',
                    'options' => [
                        'Python',
                        'Java',
                        'JavaScript',
                        'C++'
                    ],
                    'correct_option_index' => 2,
                    'points' => 1
                ],
                [
                    'question_text' => 'What does CSS stand for?',
                    'options' => [
                        'Computer Style Sheets',
                        'Cascading Style Sheets',
                        'Creative Style System',
                        'Colorful Style Sheets'
                    ],
                    'correct_option_index' => 1,
                    'points' => 1
                ],
                [
                    'question_text' => 'Which of the following is a server-side programming language?',
                    'options' => [
                        'HTML',
                        'CSS',
                        'JavaScript',
                        'PHP'
                    ],
                    'correct_option_index' => 3,
                    'points' => 2
                ]
            ],
            'Business & Management' => [
                [
                    'question_text' => 'What is SWOT analysis used for?',
                    'options' => [
                        'Financial planning',
                        'Strategic planning and analysis',
                        'Employee performance evaluation',
                        'Customer satisfaction measurement'
                    ],
                    'correct_option_index' => 1,
                    'points' => 3
                ],
                [
                    'question_text' => 'Which management function involves setting goals and determining how to achieve them?',
                    'options' => [
                        'Organizing',
                        'Planning',
                        'Leading',
                        'Controlling'
                    ],
                    'correct_option_index' => 1,
                    'points' => 2
                ],
                [
                    'question_text' => 'What is the primary goal of marketing?',
                    'options' => [
                        'To maximize profits',
                        'To satisfy customer needs and wants',
                        'To reduce costs',
                        'To increase market share'
                    ],
                    'correct_option_index' => 1,
                    'points' => 2
                ]
            ],
            'Finance & Accounting' => [
                [
                    'question_text' => 'What is the accounting equation?',
                    'options' => [
                        'Assets = Liabilities + Owner\'s Equity',
                        'Assets + Liabilities = Owner\'s Equity',
                        'Assets = Liabilities - Owner\'s Equity',
                        'Assets - Liabilities = Owner\'s Equity'
                    ],
                    'correct_option_index' => 0,
                    'points' => 3
                ],
                [
                    'question_text' => 'Which financial statement shows the company\'s financial position at a specific point in time?',
                    'options' => [
                        'Income Statement',
                        'Balance Sheet',
                        'Cash Flow Statement',
                        'Statement of Retained Earnings'
                    ],
                    'correct_option_index' => 1,
                    'points' => 2
                ],
                [
                    'question_text' => 'What is ROI an abbreviation for?',
                    'options' => [
                        'Return on Investment',
                        'Rate of Interest',
                        'Return on Income',
                        'Rate of Inflation'
                    ],
                    'correct_option_index' => 0,
                    'points' => 1
                ]
            ],
            'Marketing & Sales' => [
                [
                    'question_text' => 'What is the marketing mix commonly known as?',
                    'options' => [
                        'The 4 Ps',
                        'The 5 Cs',
                        'The 3 Rs',
                        'The 6 Ws'
                    ],
                    'correct_option_index' => 0,
                    'points' => 1
                ],
                [
                    'question_text' => 'Which of the following is NOT one of the 4 Ps of marketing?',
                    'options' => [
                        'Product',
                        'Price',
                        'Promotion',
                        'Profit'
                    ],
                    'correct_option_index' => 3,
                    'points' => 2
                ],
                [
                    'question_text' => 'What is the primary purpose of market research?',
                    'options' => [
                        'To increase sales',
                        'To understand customer needs and market conditions',
                        'To reduce competition',
                        'To improve product quality'
                    ],
                    'correct_option_index' => 1,
                    'points' => 2
                ]
            ]
        ];

        $questionsCreated = 0;
        $optionsCreated = 0;

        foreach ($questionsData as $categoryName => $questions) {
            $category = $categories->where('name', $categoryName)->first();

            if (!$category) {
                $this->command->warn("Category '{$categoryName}' not found, skipping questions.");
                continue;
            }

            foreach ($questions as $questionData) {
                // Create the question
                $question = Question::create([
                    'category_id' => $category->id,
                    'question_text' => $questionData['question_text'],
                    'points' => $questionData['points'],
                ]);

                $questionsCreated++;

                // Create options for the question
                $options = [];
                foreach ($questionData['options'] as $index => $optionText) {
                    $option = Option::create([
                        'question_id' => $question->id,
                        'option_text' => $optionText,
                        'order_by' => $index + 1,
                    ]);

                    $options[] = $option;
                    $optionsCreated++;

                    // Set the correct option
                    if ($index === $questionData['correct_option_index']) {
                        $question->update(['correct_option_id' => $option->id]);
                    }
                }
            }
        }

        $this->command->info("Successfully created {$questionsCreated} questions with {$optionsCreated} options.");
    }
}
