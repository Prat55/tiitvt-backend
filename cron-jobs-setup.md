# Installment Cron Jobs Setup Guide

This guide explains how to set up automated cron jobs for installment reminders and overdue handling in the TIITVT backend system.

## Overview

The system includes two main cron jobs:

1. **Installment Reminders**: Sends reminders 7, 5, 3, 2, and 1 day before due date
2. **Overdue Handling**: Updates overdue status and sends reminders at 0, 3, 5, 7, and 15 days after due date

## Commands Available

### 1. Installment Reminders

```bash
php artisan installments:send-reminders
```

- **Purpose**: Sends reminder emails to students before installment due dates
- **Frequency**: Should run daily (recommended: 9:00 AM)
- **Logic**: Checks for installments due in 7, 5, 3, 2, and 1 days

### 2. Overdue Installment Handling

```bash
php artisan installments:handle-overdue
```

- **Purpose**: Updates overdue statuses and sends overdue reminder emails
- **Frequency**: Should run daily (recommended: 10:00 AM)
- **Logic**:
  - Updates pending installments past due date to 'overdue' status
  - Sends overdue reminders at 0, 3, 5, 7, and 15 days after due date

## Cron Job Configuration

### Linux/Unix Cron Setup

Add these lines to your crontab (`crontab -e`):

```bash
# Installment reminders - run daily at 9:00 AM
0 9 * * * cd /path/to/your/project && php artisan installments:send-reminders >> /var/log/installment-reminders.log 2>&1

# Overdue handling - run daily at 10:00 AM
0 10 * * * cd /path/to/your/project && php artisan installments:handle-overdue >> /var/log/overdue-handling.log 2>&1
```

### Using Laravel Task Scheduler (Recommended)

If you prefer using Laravel's built-in task scheduler, add this to your `routes/console.php`:

```php
<?php

use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Artisan;

// Installment reminders - daily at 9:00 AM
Schedule::command('installments:send-reminders')
    ->daily()
    ->at('09:00')
    ->appendOutputTo(storage_path('logs/installment-reminders.log'));

// Overdue handling - daily at 10:00 AM
Schedule::command('installments:handle-overdue')
    ->daily()
    ->at('10:00')
    ->appendOutputTo(storage_path('logs/overdue-handling.log'));
```

Then set up a single cron job to run the scheduler:

```bash
# Run Laravel scheduler every minute
* * * * * cd /path/to/your/project && php artisan schedule:run >> /dev/null 2>&1
```

## Email Templates

### 1. Pre-Due Reminders

- **Location**: `resources/views/mail/notification/installment/reminder.blade.php`
- **Trigger**: 7, 5, 3, 2, and 1 days before due date
- **Content**: Friendly reminder with payment details and course information

### 2. Overdue Notifications

- **Location**: `resources/views/mail/notification/installment/overdue.blade.php`
- **Trigger**: 0, 3, 5, 7, and 15 days after due date
- **Content**: Urgent notice with overdue details and immediate action required

## Configuration

### Mail Configuration

Ensure these configuration values are set in your `.env` file:

```env
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-username
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="TIITVT System"

# CC and BCC addresses for notifications
MAIL_TO_ADDRESS=admin@yourdomain.com
MAIL_BACKUP_ADDRESS=backup@yourdomain.com
```

### Logging

The system logs all activities to Laravel's default log files:

- **Location**: `storage/logs/laravel.log`
- **Includes**: Email sending status, errors, and processing results

## Testing

### Manual Testing

Test the commands manually before setting up cron jobs:

```bash
# Test installment reminders
php artisan installments:send-reminders

# Test overdue handling
php artisan installments:handle-overdue
```

### Check Logs

Monitor the logs for successful execution:

```bash
# Check Laravel logs
tail -f storage/logs/laravel.log

# Check cron job logs (if using direct cron)
tail -f /var/log/installment-reminders.log
tail -f /var/log/overdue-handling.log
```

## Troubleshooting

### Common Issues

1. **Emails not sending**
   - Check mail configuration in `.env`
   - Verify SMTP credentials
   - Check mail server logs

2. **Commands not running**
   - Verify cron job syntax
   - Check file permissions
   - Ensure correct project path

3. **No reminders being sent**
   - Check if installments exist with correct due dates
   - Verify student email addresses
   - Check installment statuses

### Debug Mode

Enable debug logging by setting `APP_DEBUG=true` in your `.env` file for detailed error information.

## Security Considerations

1. **Email Content**: All emails are templated and sanitized
2. **Logging**: Sensitive information is not logged
3. **Rate Limiting**: Consider implementing rate limiting for email sending
4. **Access Control**: Commands can only be run via cron or authorized users

## Performance Optimization

1. **Batch Processing**: The system processes installments in batches
2. **Database Indexing**: Ensure proper indexes on `due_date` and `status` columns
3. **Queue Processing**: Consider using Laravel queues for email sending in high-volume scenarios

## Monitoring

### Health Checks

Monitor the following metrics:

- Number of reminders sent daily
- Number of overdue status updates
- Email delivery success rate
- Processing time for each command

### Alerts

Set up alerts for:

- Failed command executions
- High email failure rates
- Unusual processing times

## Support

For technical support or questions about the cron job setup, contact the development team or refer to the system documentation.
