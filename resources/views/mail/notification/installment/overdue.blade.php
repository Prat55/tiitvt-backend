@extends('mail.layout.app')

@section('content')
    <div style="max-width: 600px; margin: 0 auto; padding: 20px; background-color: #ffffff;">
        <div style="text-align: center; margin-bottom: 30px;">
            <h1 style="color: #d32f2f; margin: 0; font-size: 24px; font-weight: 600;">
                {{ $urgencyText }}: Outstanding Balance Notice
            </h1>
        </div>

        <div
            style="background-color: #ffebee; padding: 20px; border-radius: 8px; margin-bottom: 25px; border: 2px solid #f44336;">
            <p style="margin: 0; font-size: 16px; color: #c62828; font-weight: 600;">
                Dear <strong>{{ $student->first_name }} {{ $student->surname }}</strong>,
            </p>
        </div>

        <div style="margin-bottom: 25px;">
            <p style="font-size: 16px; color: #333; line-height: 1.6; margin-bottom: 15px;">
                <strong>URGENT NOTICE:</strong> You have an outstanding balance of
                <strong>₹{{ $remainingBalance }}</strong> on your course fees.
                It has been <strong>{{ $daysSinceEnrollment }} days</strong> since your enrollment.
            </p>

            <div
                style="background-color: #ffebee; border: 2px solid #f44336; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
                <p style="margin: 0; color: #c62828; font-weight: 600; text-align: center;">
                    🚨 IMMEDIATE ACTION REQUIRED - OUTSTANDING BALANCE 🚨
                </p>
            </div>

            <div style="background-color: #ffebee; border: 2px solid #f44336; padding: 20px; border-radius: 6px;">
                <h3 style="margin: 0 0 15px 0; color: #c62828; font-size: 18px;">Payment Summary:</h3>
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
                        <td style="padding: 8px 0; border-bottom: 1px solid #e0e0e0; font-weight: 600; color: #333;">
                            Outstanding Balance:</td>
                        <td style="padding: 8px 0; border-bottom: 1px solid #e0e0e0; color: #d32f2f; font-weight: 600;">
                            ₹{{ $remainingBalance }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; font-weight: 600; color: #333;">Days Since Enrollment:</td>
                        <td style="padding: 8px 0; color: #d32f2f; font-weight: 600;">{{ $daysSinceEnrollment }}
                            days</td>
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
            <h3 style="color: #333; font-size: 18px; margin-bottom: 15px;">Immediate Action Required:</h3>
            <div style="background-color: #ffebee; border: 2px solid #f44336; padding: 15px; border-radius: 6px;">
                <p style="margin: 0 0 10px 0; color: #c62828; font-weight: 600;">
                    ⚠️ Your payment is long overdue and requires immediate attention!
                </p>
                <p style="margin: 0 0 10px 0; color: #c62828;">
                    • Please make your payment as soon as possible<br>
                    • Contact your center administrator immediately for payment arrangements<br>
                    • Late fees may apply to overdue payments
                </p>
                <p style="margin: 0; color: #c62828;">
                    Failure to make payment may result in suspension of your course access.
                </p>
            </div>
        </div>

        <div style="margin-bottom: 25px;">
            <h3 style="color: #333; font-size: 18px; margin-bottom: 15px;">Payment Options:</h3>
            <div style="background-color: #f1f8e9; border: 1px solid #c8e6c9; padding: 15px; border-radius: 6px;">
                <p style="margin: 0 0 10px 0; color: #2e7d32;">
                    <strong>Available Payment Methods:</strong>
                </p>
                <ul style="margin: 0; color: #2e7d32; padding-left: 20px;">
                    <li>Cash payment at your center</li>
                    <li>Bank transfer (contact center for details)</li>
                    <li>Online payment (if available)</li>
                </ul>
            </div>
        </div>

        <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e0e0e0;">
            <p style="margin: 0; color: #666; font-size: 14px;">
                This is an automated notice. Please do not reply to this email.
            </p>
            <p style="margin: 5px 0 0 0; color: #666; font-size: 14px;">
                For immediate assistance, contact your center administrator.
            </p>
            <p style="margin: 5px 0 0 0; color: #666; font-size: 14px;">
                © {{ date('Y') }} {{ $websiteSettings->getWebsiteName() }}. All rights reserved.
            </p>
        </div>
    </div>
@endsection
