<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Course;
use App\Models\Center;
use App\Models\Student;
use App\Models\User;
use App\Models\Exam;
use App\Models\Question;
use App\Models\Invoice;
use App\Enums\RolesEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class SystemDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create categories
        $programmingCategory = Category::create([
            'name' => 'Programming',
            'description' => 'Programming and software development courses',
            'status' => 'active',
        ]);

        $webDevCategory = Category::create([
            'name' => 'Web Development',
            'description' => 'Web development and design courses',
            'status' => 'active',
        ]);

        $dataScienceCategory = Category::create([
            'name' => 'Data Science',
            'description' => 'Data science and analytics courses',
            'status' => 'active',
        ]);

        // Create courses
        $phpCourse = Course::create([
            'category_id' => $programmingCategory->id,
            'name' => 'PHP Programming',
            'description' => 'Learn PHP programming from basics to advanced',
            'duration' => '3 months',
            'fee' => 15000.00,
            'status' => 'active',
        ]);

        $laravelCourse = Course::create([
            'category_id' => $webDevCategory->id,
            'name' => 'Laravel Framework',
            'description' => 'Master Laravel PHP framework',
            'duration' => '4 months',
            'fee' => 20000.00,
            'status' => 'active',
        ]);

        $pythonCourse = Course::create([
            'category_id' => $dataScienceCategory->id,
            'name' => 'Python for Data Science',
            'description' => 'Python programming for data analysis',
            'duration' => '6 months',
            'fee' => 25000.00,
            'status' => 'active',
        ]);

        // Create center users and centers
        $center1User = User::factory()->create([
            'name' => 'Tech Center 1',
            'email' => 'techcenter1@example.com',
            'email_verified_at' => now(),
        ]);
        $center1User->assignRole(Role::where('name', 'center')->first());

        $center1 = Center::create([
            'user_id' => $center1User->id,
            'name' => 'Tech Center 1',
            'phone' => '+1234567890',
            'address' => '123 Tech Street, City',
            'status' => 'active',
        ]);

        $center2User = User::factory()->create([
            'name' => 'Code Academy',
            'email' => 'codeacademy@example.com',
            'email_verified_at' => now(),
        ]);
        $center2User->assignRole(Role::where('name', 'center')->first());

        $center2 = Center::create([
            'user_id' => $center2User->id,
            'name' => 'Code Academy',
            'phone' => '+0987654321',
            'address' => '456 Code Avenue, Town',
            'status' => 'active',
        ]);

        // Create student users and students
        $student1User = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'email_verified_at' => now(),
        ]);
        $student1User->assignRole(Role::where('name', 'student')->first());

        $student1 = Student::create([
            'center_id' => $center1->id,
            'course_id' => $phpCourse->id,
            'user_id' => $student1User->id,
            'name' => 'John Doe',
            'phone' => '+1111111111',
            'address' => '789 Student Road, Village',
            'status' => 'active',
            'fee' => 15000.00,
            'join_date' => now()->subMonths(2),
        ]);

        $student2User = User::factory()->create([
            'name' => 'Jane Smith',
            'email' => 'jane.smith@example.com',
            'email_verified_at' => now(),
        ]);
        $student2User->assignRole(Role::where('name', 'student')->first());

        $student2 = Student::create([
            'center_id' => $center2->id,
            'course_id' => $laravelCourse->id,
            'user_id' => $student2User->id,
            'name' => 'Jane Smith',
            'phone' => '+2222222222',
            'address' => '321 Learning Lane, City',
            'status' => 'active',
            'fee' => 20000.00,
            'join_date' => now()->subMonth(),
        ]);

        // Create exams
        $phpExam = Exam::create([
            'course_id' => $phpCourse->id,
            'title' => 'PHP Final Exam',
            'duration' => 120, // 2 hours
            'is_active' => true,
        ]);

        $laravelExam = Exam::create([
            'course_id' => $laravelCourse->id,
            'title' => 'Laravel Assessment',
            'duration' => 90, // 1.5 hours
            'is_active' => true,
        ]);

        // Create questions for PHP exam
        Question::create([
            'exam_id' => $phpExam->id,
            'question_text' => 'What does PHP stand for?',
            'options' => [
                'A' => 'Personal Home Page',
                'B' => 'PHP: Hypertext Preprocessor',
                'C' => 'Programming Home Page',
                'D' => 'Preprocessor Hypertext PHP'
            ],
            'correct_option' => 'B',
            'points' => 1,
        ]);

        Question::create([
            'exam_id' => $phpExam->id,
            'question_text' => 'Which of the following is used to declare a constant in PHP?',
            'options' => [
                'A' => 'const',
                'B' => 'define()',
                'C' => 'constant()',
                'D' => 'Both A and B'
            ],
            'correct_option' => 'D',
            'points' => 1,
        ]);

        // Create questions for Laravel exam
        Question::create([
            'exam_id' => $laravelExam->id,
            'question_text' => 'What is the default database driver in Laravel?',
            'options' => [
                'A' => 'MySQL',
                'B' => 'PostgreSQL',
                'C' => 'SQLite',
                'D' => 'SQL Server'
            ],
            'correct_option' => 'C',
            'points' => 1,
        ]);

        Question::create([
            'exam_id' => $laravelExam->id,
            'question_text' => 'Which command is used to create a new Laravel project?',
            'options' => [
                'A' => 'laravel new project-name',
                'B' => 'composer create-project laravel/laravel project-name',
                'C' => 'php artisan new project-name',
                'D' => 'laravel create project-name'
            ],
            'correct_option' => 'B',
            'points' => 1,
        ]);

        // Create invoices
        Invoice::create([
            'student_id' => $student1->id,
            'amount' => 15000.00,
            'status' => 'paid',
            'invoice_number' => 'INV-202501-000001',
            'description' => 'PHP Programming Course Fee',
            'paid_at' => now()->subMonth(),
        ]);

        Invoice::create([
            'student_id' => $student2->id,
            'amount' => 20000.00,
            'status' => 'unpaid',
            'invoice_number' => 'INV-202501-000002',
            'description' => 'Laravel Framework Course Fee',
        ]);
    }
}
