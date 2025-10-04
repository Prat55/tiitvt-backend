<?php

namespace Database\Seeders;

use App\Models\Student;
use App\Models\Center;
use App\Models\Course;
use App\Models\Installment;
use App\Services\StudentQRService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StudentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get active centers and courses
        $centers = Center::active()->get();
        $courses = Course::active()->get();

        if ($centers->isEmpty() || $courses->isEmpty()) {
            $this->command->warn('No active centers or courses found. Please run CenterSeeder and CourseSeeder first.');
            return;
        }

        $this->command->info('Seeding students...');

        // Create sample students with realistic data
        $studentsData = [
            [
                'first_name' => 'Rahul',
                'fathers_name' => 'Rajesh Kumar',
                'surname' => 'Sharma',
                'address' => [
                    'street' => '123, MG Road',
                    'city' => 'Mumbai',
                    'state' => 'Maharashtra',
                    'pincode' => '400001',
                    'country' => 'India'
                ],
                'telephone_no' => '022-12345678',
                'email' => 'rahul.sharma@example.com',
                'mobile' => '9876543210',
                'date_of_birth' => '1995-05-15',
                'age' => 28,
                'qualification' => 'B.Tech in Computer Science',
                'additional_qualification' => 'Certified in Python Programming',
                'reference' => 'Online Advertisement',
                'batch_time' => 'Morning (9:00 AM - 12:00 PM)',
                'scheme_given' => 'Early Bird Discount - 10% off',
                'down_payment_percentage' => 20, // 20% of course fees
                'no_of_installments' => 4,
                'installment_date' => '2024-02-01',
                'enrollment_date' => '2024-01-15',
                'incharge_name' => 'Prof. Meena Patel'
            ],
            [
                'first_name' => 'Priya',
                'fathers_name' => 'Suresh Verma',
                'surname' => 'Singh',
                'address' => [
                    'street' => '456, Park Street',
                    'city' => 'Delhi',
                    'state' => 'Delhi',
                    'pincode' => '110001',
                    'country' => 'India'
                ],
                'telephone_no' => '011-23456789',
                'email' => 'priya.singh@example.com',
                'mobile' => '8765432109',
                'date_of_birth' => '1998-08-22',
                'age' => 25,
                'qualification' => 'B.Com in Accounting',
                'additional_qualification' => 'Tally ERP9 Certified',
                'reference' => 'Friend Recommendation',
                'batch_time' => 'Evening (6:00 PM - 9:00 PM)',
                'scheme_given' => 'Student Discount - 15% off',
                'down_payment_percentage' => 100, // 100% of course fees (full payment)
                'no_of_installments' => 0,
                'installment_date' => null,
                'enrollment_date' => '2024-01-20',
                'incharge_name' => 'Prof. Amit Kumar'
            ],
            [
                'first_name' => 'Amit',
                'fathers_name' => 'Vikram Malhotra',
                'surname' => 'Gupta',
                'address' => [
                    'street' => '789, Lake View Colony',
                    'city' => 'Bangalore',
                    'state' => 'Karnataka',
                    'pincode' => '560001',
                    'country' => 'India'
                ],
                'telephone_no' => '080-34567890',
                'email' => 'amit.gupta@example.com',
                'mobile' => '7654321098',
                'date_of_birth' => '1993-12-10',
                'age' => 30,
                'qualification' => 'B.Sc in Information Technology',
                'additional_qualification' => 'AWS Certified Solutions Architect',
                'reference' => 'Job Portal',
                'batch_time' => 'Weekend (10:00 AM - 4:00 PM)',
                'scheme_given' => 'Corporate Discount - 20% off',
                'down_payment_percentage' => 30, // 30% of course fees
                'no_of_installments' => 5,
                'installment_date' => '2024-02-15',
                'enrollment_date' => '2024-01-25',
                'incharge_name' => 'Prof. Sunita Reddy'
            ],
            [
                'first_name' => 'Neha',
                'fathers_name' => 'Prakash Joshi',
                'surname' => 'Patel',
                'address' => [
                    'street' => '321, Garden Road',
                    'city' => 'Chennai',
                    'state' => 'Tamil Nadu',
                    'pincode' => '600001',
                    'country' => 'India'
                ],
                'telephone_no' => '044-45678901',
                'email' => 'neha.patel@example.com',
                'mobile' => '6543210987',
                'date_of_birth' => '1997-03-18',
                'age' => 26,
                'qualification' => 'BBA in Marketing',
                'additional_qualification' => 'Digital Marketing Certified',
                'reference' => 'Social Media',
                'batch_time' => 'Morning (8:00 AM - 11:00 AM)',
                'scheme_given' => 'First Time Learner - 25% off',
                'down_payment_percentage' => 35, // 35% of course fees
                'no_of_installments' => 3,
                'installment_date' => '2024-03-01',
                'enrollment_date' => '2024-02-01',
                'incharge_name' => 'Prof. Rajesh Iyer'
            ],
            [
                'first_name' => 'Vikram',
                'fathers_name' => 'Harish Mehta',
                'surname' => 'Shah',
                'address' => [
                    'street' => '654, Business Park',
                    'city' => 'Hyderabad',
                    'state' => 'Telangana',
                    'pincode' => '500001',
                    'country' => 'India'
                ],
                'telephone_no' => '040-56789012',
                'email' => 'vikram.shah@example.com',
                'mobile' => '5432109876',
                'date_of_birth' => '1994-07-25',
                'age' => 29,
                'qualification' => 'MCA in Computer Applications',
                'additional_qualification' => 'Microsoft Certified Professional',
                'reference' => 'College Placement Cell',
                'batch_time' => 'Evening (5:00 PM - 8:00 PM)',
                'scheme_given' => 'Alumni Discount - 30% off',
                'down_payment_percentage' => 40, // 40% of course fees
                'no_of_installments' => 6,
                'installment_date' => '2024-02-15',
                'enrollment_date' => '2024-02-10',
                'incharge_name' => 'Prof. Lakshmi Devi'
            ]
        ];

        foreach ($studentsData as $index => $studentData) {
            try {
                // Select random center and course
                $center = $centers->random();
                $course = $courses->random();

                // Get course price as course fees
                $courseFees = $course->price ?? 25000.00; // Default fallback

                // Calculate age from date of birth
                $dateOfBirth = Carbon::parse($studentData['date_of_birth']);
                $studentData['age'] = $dateOfBirth->age;

                // Validate fees logic (same as create page)
                $courseFees = $courseFees; // Use course price from database
                $downPaymentPercentage = $studentData['down_payment_percentage'] ?? 0;
                $downPayment = ($downPaymentPercentage / 100) * $courseFees;
                $noOfInstallments = $studentData['no_of_installments'] ?? 0;

                // Validate down payment
                if ($downPayment > $courseFees) {
                    $downPayment = $courseFees;
                    $studentData['down_payment'] = $downPayment;
                }

                // Calculate remaining amount
                $remainingAmount = $courseFees - $downPayment;

                // Validate installments
                if ($noOfInstallments > 0 && $remainingAmount <= 0) {
                    $noOfInstallments = 0;
                    $studentData['no_of_installments'] = 0;
                    $studentData['installment_date'] = null;
                }

                // Ensure max 24 installments
                if ($noOfInstallments > 24) {
                    $noOfInstallments = 24;
                    $studentData['no_of_installments'] = 24;
                }

                // Create student
                $student = Student::create([
                    'center_id' => $center->id,
                    'course_id' => $course->id,
                    'tiitvt_reg_no' => null, // Will be auto-generated by model boot method
                    'first_name' => $studentData['first_name'],
                    'fathers_name' => $studentData['fathers_name'],
                    'surname' => $studentData['surname'],
                    'address' => $studentData['address'],
                    'telephone_no' => $studentData['telephone_no'],
                    'email' => $studentData['email'],
                    'mobile' => $studentData['mobile'],
                    'date_of_birth' => $studentData['date_of_birth'],
                    'age' => $studentData['age'],
                    'qualification' => $studentData['qualification'],
                    'additional_qualification' => $studentData['additional_qualification'],
                    'reference' => $studentData['reference'],
                    'batch_time' => $studentData['batch_time'],
                    'scheme_given' => $studentData['scheme_given'],
                    'course_fees' => $courseFees,
                    'down_payment' => $downPayment > 0 ? $downPayment : null,
                    'no_of_installments' => $noOfInstallments > 0 ? $noOfInstallments : null,
                    'installment_date' => $studentData['installment_date'],
                    'enrollment_date' => $studentData['enrollment_date'],
                    'incharge_name' => $studentData['incharge_name']
                ]);

                // Generate QR code for the student
                $studentQRService = app(StudentQRService::class);
                $studentQRService->generateStudentQR($student);

                // Create installments if specified (same logic as create page)
                if ($noOfInstallments > 0 && $remainingAmount > 0 && $studentData['installment_date']) {
                    $this->createInstallments($student, $noOfInstallments, $remainingAmount, $studentData['installment_date']);
                }

                $this->command->info("Created student: {$student->first_name} {$student->surname} (ID: {$student->id})");
            } catch (\Exception $e) {
                $this->command->error("Failed to create student {$studentData['first_name']}: " . $e->getMessage());
            }
        }

        $this->command->info('Student seeding completed!');
    }

    /**
     * Create installments for a student (same logic as create page)
     */
    private function createInstallments($student, $noOfInstallments, $remainingAmount, $installmentDate): void
    {
        $installmentAmount = round($remainingAmount / $noOfInstallments, 2);
        $remainingForLastInstallment = $remainingAmount;

        for ($i = 1; $i <= $noOfInstallments; $i++) {
            if ($i == $noOfInstallments) {
                // Last installment gets the remaining amount to avoid rounding errors
                $amount = round($remainingForLastInstallment, 2);
            } else {
                $amount = $installmentAmount;
                $remainingForLastInstallment -= $amount;
            }

            $dueDate = Carbon::parse($installmentDate)->addMonths($i - 1);

            Installment::create([
                'student_id' => $student->id,
                'installment_no' => $i,
                'amount' => $amount,
                'due_date' => $dueDate,
                'status' => 'pending',
            ]);
        }

        $this->command->info("Created {$noOfInstallments} installments for student {$student->first_name}");
    }
}
