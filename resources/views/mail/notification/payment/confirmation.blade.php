@extends('mail.layout.app')

@section('content')
    <div style="max-width: 600px; margin: 0 auto; padding: 20px; background-color: #ffffff;">
        <div style="text-align: center; margin-bottom: 30px;">
            <h1 style="color: #28a745; margin: 0; font-size: 24px; font-weight: 600;">
                ✅ Payment Confirmed!
            </h1>
            <p style="color: #6c757d; margin: 10px 0 0 0; font-size: 16px;">
                Thank you for your payment
            </p>
        </div>

        <div style="background-color: #d4edda; padding: 20px; border-radius: 8px; margin-bottom: 25px; border: 2px solid #c3e6cb;">
            <p style="margin: 0; font-size: 16px; color: #155724; font-weight: 600;">
                Dear <strong>{{ $studentName }}</strong>,
            </p>
        </div>

        <div style="margin-bottom: 25px;">
            <p style="font-size: 16px; color: #333; line-height: 1.6; margin-bottom: 15px;">
                We're pleased to confirm that we have received your payment. Your transaction has been processed successfully.
            </p>

            <div style="background-color: #f8f9fa; border: 2px solid #28a745; padding: 20px; border-radius: 6px; margin-bottom: 20px;">
                <h3 style="color: #155724; margin: 0 0 15px 0; font-size: 18px;">💰 Payment Details</h3>
                
                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                    <span style="color: #495057; font-weight: 600;">Amount Paid:</span>
                    <span style="color: #28a745; font-weight: 600; font-size: 18px;">{{ $paymentDetails['amount'] ?? 'N/A' }}</span>
                </div>
                
                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                    <span style="color: #495057; font-weight: 600;">Payment Date:</span>
                    <span style="color: #495057;">{{ $paymentDetails['date'] ?? 'N/A' }}</span>
                </div>
                
                @if(isset($paymentDetails['transaction_id']))
                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                        <span style="color: #495057; font-weight: 600;">Transaction ID:</span>
                        <span style="color: #495057; font-family: monospace;">{{ $paymentDetails['transaction_id'] }}</span>
                    </div>
                @endif
                
                @if(isset($paymentDetails['next_due_date']))
                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                        <span style="color: #495057; font-weight: 600;">Next Due Date:</span>
                        <span style="color: #495057;">{{ $paymentDetails['next_due_date'] }}</span>
                    </div>
                @endif
            </div>

            <div style="background-color: #e7f3ff; border: 1px solid #b3d9ff; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
                <p style="margin: 0; color: #0056b3; font-weight: 600; text-align: center;">
                    🎯 Your payment has been applied to your account successfully!
                </p>
            </div>

            <p style="font-size: 16px; color: #333; line-height: 1.6; margin-bottom: 15px;">
                What happens next:
            </p>

            <ul style="color: #333; line-height: 1.6; margin-bottom: 20px; padding-left: 20px;">
                <li style="margin-bottom: 8px;">📧 <strong>Confirmation Email:</strong> This email serves as your payment receipt</li>
                <li style="margin-bottom: 8px;">📊 <strong>Account Updated:</strong> Your student account has been updated with this payment</li>
                <li style="margin-bottom: 8px;">📚 <strong>Continue Learning:</strong> You can now access your course materials and continue your studies</li>
                <li style="margin-bottom: 8px;">📅 <strong>Next Payment:</strong> We'll send you a reminder when your next payment is due</li>
            </ul>
        </div>

        <div style="background-color: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
            <h3 style="color: #856404; margin: 0 0 10px 0; font-size: 16px;">💡 Important Notes</h3>
            <ul style="color: #856404; margin: 0; padding-left: 20px; font-size: 14px;">
                <li style="margin-bottom: 5px;">Keep this email for your records</li>
                <li style="margin-bottom: 5px;">Payment processing may take 1-2 business days to reflect in your bank statement</li>
                <li style="margin-bottom: 5px;">Contact support if you have any questions about your payment</li>
            </ul>
        </div>

        <div style="background-color: #f8f9fa; padding: 20px; border-radius: 8px; border: 1px solid #dee2e6;">
            <h3 style="color: #495057; margin: 0 0 15px 0; font-size: 18px;">📧 Need Help?</h3>
            <p style="margin: 0 0 10px 0; color: #495057; font-size: 14px;">
                <strong>Student Portal:</strong> <a href="{{ config('app.url') }}" style="color: #007bff;">{{ config('app.url') }}</a>
            </p>
            <p style="margin: 0 0 10px 0; color: #495057; font-size: 14px;">
                <strong>Support Email:</strong> <a href="mailto:{{ config('app.mail.support.address', 'support@tiitvt.com') }}" style="color: #007bff;">{{ config('app.mail.support.address', 'support@tiitvt.com') }}</a>
            </p>
            <p style="margin: 0; color: #495057; font-size: 14px;">
                <strong>Phone Support:</strong> Available during business hours
            </p>
        </div>

        <div style="text-align: center; margin-top: 30px;">
            <p style="color: #6c757d; font-size: 14px; margin: 0;">
                Thank you for choosing TIITVT for your education!
            </p>
            <p style="color: #6c757d; font-size: 14px; margin: 5px 0 0 0;">
                Best regards,<br><strong>The TIITVT Team</strong>
            </p>
        </div>
    </div>
@endsection
