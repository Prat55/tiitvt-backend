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
            'slug' => 'programming',
            'description' => 'Programming and software development courses',
            'is_active' => true,
        ]);

        $webDevCategory = Category::create([
            'name' => 'Web Development',
            'slug' => 'web-development',
            'description' => 'Web development and design courses',
            'is_active' => true,
        ]);

        $dataScienceCategory = Category::create([
            'name' => 'Data Science',
            'slug' => 'data-science',
            'description' => 'Data science and analytics courses',
            'is_active' => true,
        ]);

        // Create courses
        $phpCourse = Course::create([
            'name' => 'PHP Programming',
            'slug' => 'php-programming',
            'description' => 'Learn PHP programming from basics to advanced',
            'duration' => '3 months',
            'price' => 15000.00,
            'is_active' => true,
        ]);

        $laravelCourse = Course::create([
            'name' => 'Laravel Framework',
            'slug' => 'laravel-framework',
            'description' => 'Master Laravel PHP framework',
            'duration' => '4 months',
            'price' => 20000.00,
            'is_active' => true,
        ]);

        $pythonCourse = Course::create([
            'name' => 'Python for Data Science',
            'slug' => 'python-data-science',
            'description' => 'Python programming for data analysis',
            'duration' => '6 months',
            'price' => 25000.00,
            'is_active' => true,
        ]);

        // Create course-category relationships
        $programmingCategory->courses()->attach($phpCourse->id);
        $webDevCategory->courses()->attach($laravelCourse->id);
        $dataScienceCategory->courses()->attach($pythonCourse->id);

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
            'tiitvt_reg_no' => 'TIITVT/ATC/21/1',
            'first_name' => 'John',
            'fathers_name' => 'Doe',
            'surname' => 'Smith',
            'email' => 'john.doe@example.com',
            'mobile' => '+1111111111',
            'address' => [
                'street' => '789 Student Road',
                'city' => 'Village',
                'state' => 'State',
                'pincode' => '123456',
                'country' => 'India'
            ],
            'course_fees' => 15000.00,
            'enrollment_date' => now()->subMonths(2),
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
            'tiitvt_reg_no' => 'TIITVT/ATC/22/2',
            'first_name' => 'Jane',
            'fathers_name' => 'Smith',
            'surname' => 'Johnson',
            'email' => 'jane.smith@example.com',
            'mobile' => '+2222222222',
            'address' => [
                'street' => '321 Learning Lane',
                'city' => 'City',
                'state' => 'State',
                'pincode' => '654321',
                'country' => 'India'
            ],
            'course_fees' => 20000.00,
            'enrollment_date' => now()->subMonth(),
        ]);

        // Create exams
        $phpExam = Exam::create([
            'course_id' => $phpCourse->id,
            'center_id' => $center1->id,
            'duration' => 120, // 2 hours
            'date' => now()->addDays(7),
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
            'status' => 'scheduled',
        ]);

        $laravelExam = Exam::create([
            'course_id' => $laravelCourse->id,
            'center_id' => $center2->id,
            'duration' => 90, // 1.5 hours
            'date' => now()->addDays(10),
            'start_time' => '14:00:00',
            'end_time' => '15:30:00',
            'status' => 'scheduled',
        ]);

        // Create questions for PHP exam
        $phpQuestion1 = Question::create([
            'category_id' => $programmingCategory->id,
            'question_text' => 'What does PHP stand for?',
            'points' => 1,
        ]);

        // Create options for PHP question 1
        $phpQuestion1->options()->createMany([
            ['option_text' => 'Personal Home Page'],
            ['option_text' => 'PHP: Hypertext Preprocessor'],
            ['option_text' => 'Programming Home Page'],
            ['option_text' => 'Preprocessor Hypertext PHP']
        ]);

        // Update question with correct option ID (option B - index 1)
        $correctOption1 = $phpQuestion1->options()->where('option_text', 'PHP: Hypertext Preprocessor')->first();
        $phpQuestion1->update(['correct_option_id' => $correctOption1->id]);

        $phpQuestion2 = Question::create([
            'category_id' => $programmingCategory->id,
            'question_text' => 'Which of the following is used to declare a constant in PHP?',
            'points' => 1,
        ]);

        // Create options for PHP question 2
        $phpQuestion2->options()->createMany([
            ['option_text' => 'const'],
            ['option_text' => 'define()'],
            ['option_text' => 'constant()'],
            ['option_text' => 'Both A and B']
        ]);

        // Update question with correct option ID (option D - index 3)
        $correctOption2 = $phpQuestion2->options()->where('option_text', 'Both A and B')->first();
        $phpQuestion2->update(['correct_option_id' => $correctOption2->id]);

        // Create questions for Laravel exam
        $laravelQuestion1 = Question::create([
            'category_id' => $webDevCategory->id,
            'question_text' => 'What is the default database driver in Laravel?',
            'points' => 1,
        ]);

        // Create options for Laravel question 1
        $laravelQuestion1->options()->createMany([
            ['option_text' => 'MySQL'],
            ['option_text' => 'PostgreSQL'],
            ['option_text' => 'SQLite'],
            ['option_text' => 'SQL Server']
        ]);

        // Update question with correct option ID (option C - index 2)
        $correctOption3 = $laravelQuestion1->options()->where('option_text', 'SQLite')->first();
        $laravelQuestion1->update(['correct_option_id' => $correctOption3->id]);

        $laravelQuestion2 = Question::create([
            'category_id' => $webDevCategory->id,
            'question_text' => 'Which command is used to create a new Laravel project?',
            'points' => 1,
        ]);

        // Create options for Laravel question 2
        $laravelQuestion2->options()->createMany([
            ['option_text' => 'laravel new project-name'],
            ['option_text' => 'composer create-project laravel/laravel project-name'],
            ['option_text' => 'php artisan new project-name'],
            ['option_text' => 'laravel create project-name']
        ]);

        // Update question with correct option ID (option B - index 1)
        $correctOption4 = $laravelQuestion2->options()->where('option_text', 'composer create-project laravel/laravel project-name')->first();
        $laravelQuestion2->update(['correct_option_id' => $correctOption4->id]);

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
