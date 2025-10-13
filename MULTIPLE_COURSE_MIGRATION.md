# Multiple Course Selection Migration

## Overview
This migration converts the student-course relationship from a one-to-many (single course per student) to a many-to-many (multiple courses per student) relationship while preserving all existing data. **Important**: Fees are handled at the student level (single fee structure), not per course.

## Changes Made

### 1. Database Migrations
- **Created `student_courses` pivot table** - Stores the many-to-many relationship with course enrollment details (no fees)
- **Data migration** - Moves existing course enrollment data from `students` table to the new pivot table
- **Removed old columns** - Removes `course_id` and course-specific fields from `students` table, **keeps fee fields**

### 2. Model Updates
- **Student Model**: Updated to use `belongsToMany` relationship with courses, **fees remain in students table**
- **Course Model**: Updated to use `belongsToMany` relationship with students
- **StudentCourse Model**: New model for managing course enrollment details (no fees)
- **Backward Compatibility**: Added accessor methods to maintain compatibility with existing code

### 3. Fee Structure
- **Single Fee System**: All fees (course_fees, down_payment, installments) are stored in the `students` table
- **Multiple Courses**: Students can enroll in multiple courses but pay a single fee amount
- **Course Details**: Each course enrollment stores enrollment_date, course_taken, batch_time, etc.

### 4. Backward Compatibility
The Student model now includes accessor methods that return data from the "primary" (first) course:
- `$student->course` - Returns the first enrolled course
- `$student->course_id` - Returns the ID of the first enrolled course
- `$student->course_fees` - Returns fees from students table (single fee for all courses)
- `$student->down_payment` - Returns down payment from students table
- `$student->no_of_installments` - Returns installments from students table
- And other course-related fields from the primary course...

## Usage Examples

### Enrolling a Student in Multiple Courses
```php
$student = Student::find(1);

// Set the single fee amount for all courses
$student->update([
    'course_fees' => 8000,  // Single fee for all courses
    'down_payment' => 2000,
    'no_of_installments' => 4
]);

// Enroll in multiple courses (no fees in pivot table)
$student->courses()->attach([
    1 => [
        'enrollment_date' => now(),
        'course_taken' => 'Full Stack Development',
        'batch_time' => 'Morning',
        'incharge_name' => 'John Doe'
    ],
    2 => [
        'enrollment_date' => now(),
        'course_taken' => 'Data Science',
        'batch_time' => 'Evening',
        'incharge_name' => 'Jane Smith'
    ]
]);
```

### Getting All Courses for a Student
```php
$student = Student::with('courses')->find(1);

// Display single fee for all courses
echo "Total Fees: ₹" . $student->course_fees;

foreach ($student->courses as $course) {
    echo $course->name . ' - Enrollment: ' . $course->pivot->enrollment_date;
    echo ' - Batch: ' . $course->pivot->batch_time;
}
```

### Getting All Students for a Course
```php
$course = Course::with('students')->find(1);

foreach ($course->students as $student) {
    echo $student->first_name . ' - Fees: ₹' . $student->course_fees;
    echo ' - Enrollment: ' . $student->pivot->enrollment_date;
}
```

### Using StudentCourse Model for Detailed Management
```php
// Create a new enrollment (no fees - fees are in students table)
StudentCourse::create([
    'student_id' => 1,
    'course_id' => 2,
    'enrollment_date' => now(),
    'course_taken' => 'Mobile Development',
    'batch_time' => 'Afternoon',
    'incharge_name' => 'Mike Johnson'
]);

// Update enrollment details
$enrollment = StudentCourse::where('student_id', 1)->where('course_id', 2)->first();
$enrollment->update(['batch_time' => 'Evening']);

// Update student fees (affects all courses)
$student = Student::find(1);
$student->update(['course_fees' => 10000]);
```

## Migration Steps

1. **Run the migrations in order:**
   ```bash
   php artisan migrate
   ```

2. **Verify data migration:**
   ```bash
   # Check that all students have their course data in the pivot table
   php artisan tinker
   >>> Student::with('courses')->get()->each(function($s) { 
   ...     echo $s->first_name . ' - Courses: ' . $s->courses->count() . "\n"; 
   ... });
   ```

## Important Notes

1. **Existing Views**: Most existing views will continue to work due to backward compatibility accessors
2. **Primary Course**: The system treats the first enrolled course as the "primary" course for backward compatibility
3. **Data Integrity**: All existing data is preserved during migration
4. **Future Development**: New features can take advantage of multiple course enrollment

## Next Steps for Full Implementation

1. **Update Forms**: Modify student creation/edit forms to allow multiple course selection
2. **Update Views**: Enhance views to display all enrolled courses instead of just the primary one
3. **Update Reports**: Modify reports to handle multiple course enrollments
4. **Update Notifications**: Ensure notifications work with multiple course enrollments
5. **Add Course Management**: Create interfaces for managing student course enrollments

## Rollback Considerations

If you need to rollback this migration:
1. Restore from database backup (recommended)
2. The data migration is not reversible as it moves data between tables
3. Consider the impact on any new features built on multiple course enrollment
