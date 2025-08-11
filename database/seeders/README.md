# Database Seeders Documentation

This directory contains database seeders for populating the TIITVT backend system with sample data.

## Available Seeders

### 1. UserWithRoleSeeder

- Creates users with different roles
- Sets up basic user authentication

### 2. CenterSeeder

- Creates sample training centers
- Includes active and inactive centers
- Generates realistic center data with Indian locations

### 3. CourseSeeder

- Creates course categories (Computer Science, Business, Design, etc.)
- Generates sample courses with realistic descriptions and fees
- Sets up the foundation for student enrollment

### 4. StudentSeeder

- Creates sample students with comprehensive data
- Implements the same logic as the create student page
- Automatically generates installments based on fee structure
- Validates business rules (down payment, installments, etc.)

### 5. StudentOnlySeeder

- Standalone seeder for testing student creation
- Can be run independently without other seeders
- Useful for development and testing purposes

## Running Seeders

### Run All Seeders

```bash
php artisan db:seed
```

### Run Specific Seeders

```bash
# Run only student seeder
php artisan db:seed --class=StudentSeeder

# Run only course seeder
php artisan db:seed --class=CourseSeeder

# Run standalone student seeder
php artisan db:seed --class=StudentOnlySeeder
```

### Run Seeders in Order

```bash
# First, ensure centers and courses exist
php artisan db:seed --class=CenterSeeder
php artisan db:seed --class=CourseSeeder

# Then run student seeder
php artisan db:seed --class=StudentSeeder
```

## Student Seeder Features

The StudentSeeder implements the exact same logic as the create student page:

### Validation Logic

- **Down Payment Validation**: Ensures down payment doesn't exceed course fees
- **Installment Validation**: Prevents installments when remaining amount is zero
- **Maximum Installments**: Caps at 24 installments maximum
- **Age Calculation**: Automatically calculates age from date of birth

### Installment Creation

- **Automatic Calculation**: Divides remaining amount evenly across installments
- **Rounding Handling**: Last installment gets remaining amount to avoid rounding errors
- **Due Date Calculation**: Sets due dates based on installment date and frequency
- **Status Management**: All installments start with 'pending' status

### Sample Data

The seeder creates 5 realistic students with:

- **Diverse Backgrounds**: Different qualifications, cities, and course preferences
- **Realistic Fees**: Varied course fees from ₹18,000 to ₹55,000
- **Payment Plans**: Mix of full payment and installment plans
- **Geographic Distribution**: Students from different Indian cities
- **Professional References**: Various sources like online ads, friends, job portals

### Business Rules Implemented

1. **Fee Structure Validation**
   - Down payment ≤ Course fees
   - Installments only when remaining amount > 0
   - Maximum 24 installments

2. **Data Integrity**
   - Unique email addresses
   - Valid center and course relationships
   - Proper address formatting
   - Realistic contact information

3. **Installment Logic**
   - Monthly due dates
   - Proper amount distribution
   - Status tracking

## Dependencies

- **CenterSeeder**: Must run before StudentSeeder
- **CourseSeeder**: Must run before StudentSeeder
- **UserWithRoleSeeder**: Required for center creation

## Customization

To modify the seeder data:

1. Edit the `$studentsData` array in the seeder
2. Adjust fee structures and installment plans
3. Modify sample addresses and contact information
4. Change qualification and reference data

## Error Handling

The seeders include comprehensive error handling:

- Validation of required relationships
- Graceful failure with informative messages
- Transaction safety for data integrity
- Detailed logging of creation process

## Testing

Use the `StudentOnlySeeder` for testing:

```bash
php artisan db:seed --class=StudentOnlySeeder
```

This seeder can run independently and is perfect for development and testing scenarios.
