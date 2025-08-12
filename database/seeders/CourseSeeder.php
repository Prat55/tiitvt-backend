<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CourseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create course categories first
        $categories = [
            'Computer Science & IT',
            'Business & Management',
            'Engineering & Technology',
            'Design & Creative Arts',
            'Healthcare & Medical',
            'Finance & Accounting',
            'Marketing & Sales',
            'Education & Training'
        ];

        foreach ($categories as $categoryName) {
            Category::firstOrCreate([
                'name' => $categoryName,
                'slug' => Str::slug($categoryName),
                'is_active' => true
            ]);
        }

        $this->command->info('Course categories created.');

        // Create sample courses
        $coursesData = [
            [
                'name' => 'Full Stack Web Development',
                'category_name' => 'Computer Science & IT',
                'description' => 'Comprehensive course covering frontend and backend web development technologies including HTML, CSS, JavaScript, PHP, MySQL, and modern frameworks.',
                'meta_description' => 'Learn full-stack web development with HTML, CSS, JavaScript, PHP, MySQL, and modern frameworks. Comprehensive 6-month course for complete beginners.',
                'duration' => '6 months',
                'mrp' => 55000.00,
                'price' => 45000.00,
                'is_active' => true
            ],
            [
                'name' => 'Data Science & Analytics',
                'category_name' => 'Computer Science & IT',
                'description' => 'Learn data analysis, machine learning, and statistical modeling using Python, R, and various data science tools.',
                'meta_description' => 'Master data science and analytics with Python, R, machine learning, and statistical modeling. 8-month intensive course for data professionals.',
                'duration' => '8 months',
                'mrp' => 70000.00,
                'price' => 55000.00,
                'is_active' => true
            ],
            [
                'name' => 'Digital Marketing',
                'category_name' => 'Marketing & Sales',
                'description' => 'Master digital marketing strategies including SEO, SEM, social media marketing, content marketing, and analytics.',
                'meta_description' => 'Master digital marketing strategies: SEO, SEM, social media, content marketing, and analytics. 4-month course for marketing professionals.',
                'duration' => '4 months',
                'mrp' => 35000.00,
                'price' => 25000.00,
                'is_active' => true
            ],
            [
                'name' => 'Business Administration',
                'category_name' => 'Business & Management',
                'description' => 'Comprehensive business management course covering operations, finance, marketing, and strategic planning.',
                'meta_description' => 'Comprehensive business administration course covering operations, finance, marketing, and strategic planning. 12-month program.',
                'duration' => '12 months',
                'mrp' => 95000.00,
                'price' => 75000.00,
                'is_active' => true
            ],
            [
                'name' => 'Graphic Design & UI/UX',
                'category_name' => 'Design & Creative Arts',
                'description' => 'Learn graphic design principles, UI/UX design, and use industry-standard tools like Adobe Creative Suite and Figma.',
                'meta_description' => 'Learn graphic design principles, UI/UX design with Adobe Creative Suite and Figma. 5-month course for creative professionals.',
                'duration' => '5 months',
                'mrp' => 45000.00,
                'price' => 35000.00,
                'is_active' => true
            ],
            [
                'name' => 'Financial Accounting',
                'category_name' => 'Finance & Accounting',
                'description' => 'Master accounting principles, financial statements, tax preparation, and use of accounting software like Tally and QuickBooks.',
                'meta_description' => 'Master accounting principles, financial statements, tax preparation with Tally and QuickBooks. 6-month course for finance professionals.',
                'duration' => '6 months',
                'mrp' => 40000.00,
                'price' => 30000.00,
                'is_active' => true
            ],
            [
                'name' => 'Mobile App Development',
                'category_name' => 'Computer Science & IT',
                'description' => 'Learn to develop mobile applications for iOS and Android using React Native, Flutter, and native development.',
                'meta_description' => 'Learn mobile app development for iOS and Android using React Native, Flutter, and native development. 7-month course.',
                'duration' => '7 months',
                'mrp' => 65000.00,
                'price' => 50000.00,
                'is_active' => true
            ],
            [
                'name' => 'Project Management',
                'category_name' => 'Business & Management',
                'description' => 'Learn project management methodologies, tools, and best practices to lead successful projects.',
                'meta_description' => 'Learn project management methodologies, tools, and best practices to lead successful projects. 4-month intensive course.',
                'duration' => '4 months',
                'mrp' => 38000.00,
                'price' => 28000.00,
                'is_active' => true
            ]
        ];

        foreach ($coursesData as $courseData) {
            try {
                // Create the course
                $course = Course::firstOrCreate([
                    'name' => $courseData['name']
                ], [
                    'slug' => Str::slug($courseData['name']),
                    'description' => $courseData['description'],
                    'meta_description' => $courseData['meta_description'],
                    'duration' => $courseData['duration'],
                    'mrp' => $courseData['mrp'],
                    'price' => $courseData['price'],
                    'is_active' => $courseData['is_active']
                ]);

                // Get the category
                $category = Category::where('name', $courseData['category_name'])->first();

                if ($category) {
                    // Create the relationship in the pivot table
                    \App\Models\CourseCategory::firstOrCreate([
                        'category_id' => $category->id,
                        'course_id' => $course->id
                    ]);
                }

                $this->command->info("Created course: {$courseData['name']}");
            } catch (\Exception $e) {
                $this->command->error("Failed to create course {$courseData['name']}: " . $e->getMessage());
            }
        }

        $this->command->info('Course seeding completed!');
    }
}
