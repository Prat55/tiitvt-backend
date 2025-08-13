@extends('mail.layout.app')

@section('content')
    <div style="max-width: 600px; margin: 0 auto; padding: 20px; background-color: #ffffff;">
        <div style="text-align: center; margin-bottom: 30px;">
            <h1 style="color: #d32f2f; margin: 0; font-size: 24px; font-weight: 600;">
                {{ $urgencyText }}: Installment Payment Reminder
            </h1>
        </div>

        <div style="background-color: #f5f5f5; padding: 20px; border-radius: 8px; margin-bottom: 25px;">
            <p style="margin: 0; font-size: 16px; color: #333;">
                Dear <strong>{{ $student->first_name }} {{ $student->surname }}</strong>,
            </p>
        </div>

        <div style="margin-bottom: 25px;">
            <p style="font-size: 16px; color: #333; line-height: 1.6; margin-bottom: 15px;">
                This is a friendly reminder that your installment payment is due in <strong>{{ $days }}
                    day{{ $days > 1 ? 's' : '' }}</strong>.
            </p>

            @if ($days === 1)
                <div
                    style="background-color: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
                    <p style="margin: 0; color: #856404; font-weight: 600;">
                        ⚠️ URGENT: Your payment is due tomorrow!
                    </p>
                </div>
            @endif

            <div style="background-color: #e3f2fd; border: 1px solid #bbdefb; padding: 20px; border-radius: 6px;">
                <h3 style="margin: 0 0 15px 0; color: #1976d2; font-size: 18px;">Payment Details:</h3>
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="padding: 8px 0; border-bottom: 1px solid #e0e0e0; font-weight: 600; color: #333;">Student
                            ID:</td>
                        <td style="padding: 8px 0; border-bottom: 1px solid #e0e0e0; color: #666;">
                            {{ $student->tiitvt_reg_no }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; border-bottom: 1px solid #e0e0e0; font-weight: 600; color: #333;">
                            Installment No:</td>
                        <td style="padding: 8px 0; border-bottom: 1px solid #e0e0e0; color: #666;">
                            {{ $installment->installment_no }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; border-bottom: 1px solid #e0e0e0; font-weight: 600; color: #333;">Amount
                            Due:</td>
                        <td style="padding: 8px 0; border-bottom: 1px solid #e0e0e0; color: #d32f2f; font-weight: 600;">
                            ₹{{ $amount }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; font-weight: 600; color: #333;">Due Date:</td>
                        <td style="padding: 8px 0; color: #666;">{{ $dueDate }}</td>
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
                    Please ensure your payment is made before the due date to avoid any late fees or penalties.
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
                © {{ date('Y') }} TIITVT. All rights reserved.
            </p>
        </div>
    </div>
@endsection
