@extends('mail.layout.app')

@section('content')
    <div style="max-width: 600px; margin: 0 auto; padding: 20px; background-color: #ffffff;">
        <div style="text-align: center; margin-bottom: 30px;">
            <h1 style="color: #1976d2; margin: 0; font-size: 24px; font-weight: 600;">
                {{ $urgencyText }}: Payment Reminder
            </h1>
        </div>

        <div style="background-color: #f5f5f5; padding: 20px; border-radius: 8px; margin-bottom: 25px;">
            <p style="margin: 0; font-size: 16px; color: #333;">
                Dear <strong>{{ $student->first_name }} {{ $student->surname }}</strong>,
            </p>
        </div>

        <div style="margin-bottom: 25px;">
            <p style="font-size: 16px; color: #333; line-height: 1.6; margin-bottom: 15px;">
                This is a friendly reminder that you have an outstanding balance on your course fees.
                It has been <strong>{{ $daysSinceEnrollment }} days</strong> since your enrollment.
            </p>

            <div style="background-color: #e3f2fd; border: 1px solid #bbdefb; padding: 20px; border-radius: 6px;">
                <h3 style="margin: 0 0 15px 0; color: #1976d2; font-size: 18px;">Payment Summary:</h3>
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="padding: 8px 0; border-bottom: 1px solid #e0e0e0; font-weight: 600; color: #333;">Student
                            ID:</td>
                        <td style="padding: 8px 0; border-bottom: 1px solid #e0e0e0; color: #666;">
                            {{ $student->tiitvt_reg_no }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; border-bottom: 1px solid #e0e0e0; font-weight: 600; color: #333;">
                            Enrollment Date:</td>
                        <td style="padding: 8px 0; border-bottom: 1px solid #e0e0e0; color: #666;">
                            {{ $enrollmentDate }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; border-bottom: 1px solid #e0e0e0; font-weight: 600; color: #333;">Total
                            Fees:</td>
                        <td style="padding: 8px 0; border-bottom: 1px solid #e0e0e0; color: #666;">
                            ₹{{ $totalFees }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; border-bottom: 1px solid #e0e0e0; font-weight: 600; color: #333;">Total
                            Paid:</td>
                        <td style="padding: 8px 0; border-bottom: 1px solid #e0e0e0; color: #2e7d32; font-weight: 600;">
                            ₹{{ $totalPaid }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; font-weight: 600; color: #333;">Remaining Balance:</td>
                        <td style="padding: 8px 0; color: #d32f2f; font-weight: 600;">
                            ₹{{ $remainingBalance }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <div style="margin-bottom: 25px;">
            <h3 style="color: #333; font-size: 18px; margin-bottom: 15px;">Course Information:</h3>
            <div style="background-color: #f8f9fa; padding: 15px; border-radius: 6px;">
                <p style="margin: 0; color: #666;">
                    <strong>Course:</strong> {{ $student->course->name ?? 'N/A' }}<br>
                    <strong>Center:</strong> {{ $student->center->name ?? 'N/A' }}
                </p>
            </div>
        </div>

        <div style="margin-bottom: 25px;">
            <h3 style="color: #333; font-size: 18px; margin-bottom: 15px;">Payment Instructions:</h3>
            <div style="background-color: #f1f8e9; border: 1px solid #c8e6c9; padding: 15px; border-radius: 6px;">
                <p style="margin: 0 0 10px 0; color: #2e7d32;">
                    Please make your payment at the earliest to keep your account in good standing.
                </p>
                <p style="margin: 0; color: #2e7d32;">
                    For payment assistance or questions, please contact your center administrator.
                </p>
            </div>
        </div>

        <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e0e0e0;">
            <p style="margin: 0; color: #666; font-size: 14px;">
                This is an automated reminder. Please do not reply to this email.
            </p>
            <p style="margin: 5px 0 0 0; color: #666; font-size: 14px;">
                © {{ date('Y') }} {{ $websiteSettings->getWebsiteName() }}. All rights reserved.
            </p>
        </div>
    </div>
@endsection
