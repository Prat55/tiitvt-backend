# Question Seeder Documentation

This document explains how to use the Question Seeder for populating your database with sample questions and options.

## Overview

The Question Seeder creates sample questions with multiple-choice options across different categories. Each question includes:

- Question text
- 4 multiple-choice options
- Correct answer identification
- Point values (1, 2, 3, or 5 points)
- Category association

## Files Created

1. **`database/seeders/QuestionSeeder.php`** - Main seeder integrated with DatabaseSeeder
2. **`database/seeders/QuestionOnlySeeder.php`** - Standalone seeder for testing
3. **`database/factories/QuestionFactory.php`** - Factory for generating random question data
4. **`database/factories/OptionFactory.php`** - Factory for generating random option data

## Prerequisites

Before running the Question Seeder, ensure you have:

- Categories in your database (run `CourseSeeder` first as it creates categories)
- The `questions` and `options` tables migrated

## Usage

### Option 1: Run All Seeders

```bash
php artisan db:seed
```

### Option 2: Run Only Question Seeder

```bash
php artisan db:seed --class=QuestionSeeder
```

### Option 3: Run Standalone Question Seeder

```bash
php artisan db:seed --class=QuestionOnlySeeder
```

## Sample Questions Created

The seeder creates questions in the following categories:

### Computer Science & IT

- HTML purpose and usage
- JavaScript as web language
- CSS definition
- Server-side programming languages

### Business & Management

- SWOT analysis
- Management functions
- Marketing goals

### Finance & Accounting

- Accounting equation
- Financial statements
- ROI definition

### Marketing & Sales

- Marketing mix (4 Ps)
- Marketing fundamentals
- Market research purpose

## Question Structure

Each question follows this structure:

```php
[
    'question_text' => 'What is the primary purpose of HTML?',
    'options' => [
        'To style web pages',
        'To structure and organize web content',  // Correct answer
        'To add interactivity to websites',
        'To manage databases'
    ],
    'correct_option_index' => 1,  // Index of correct option (0-based)
    'points' => 2
]
```

## Database Relationships

- **Question** belongs to **Category**
- **Question** has many **Options**
- **Question** belongs to **Option** (correct answer)
- **Option** belongs to **Question**

## Customization

To add more questions or modify existing ones:

1. Edit the `$questionsData` array in the seeder
2. Follow the same structure as existing questions
3. Ensure category names match those in your database
4. Run the seeder again

## Troubleshooting

### "No categories found" Warning

- Run `php artisan db:seed --class=CourseSeeder` first to create categories

### Foreign Key Constraint Errors

- Ensure all required tables are migrated
- Check that category IDs exist before creating questions

### Duplicate Questions

- The seeder doesn't check for duplicates
- Clear your database or add duplicate checking logic if needed

## Factory Usage

You can also use the factories to generate random data:

```php
// Generate a single question with options
$question = Question::factory()->create();
$options = Option::factory()->count(4)->create(['question_id' => $question->id]);

// Generate multiple questions
$questions = Question::factory()->count(10)->create();
```

## Points System

Questions use a weighted points system:

- **1 point**: Basic knowledge questions
- **2 points**: Standard difficulty questions
- **3 points**: Advanced knowledge questions
- **5 points**: Expert-level questions

This allows for more sophisticated scoring in exams and assessments.
