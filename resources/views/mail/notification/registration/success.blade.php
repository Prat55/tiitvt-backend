@extends('mail.layout.app')

@section('content')
    <div style="max-width: 600px; margin: 0 auto; padding: 20px; background-color: #ffffff;">
        <div style="text-align: center; margin-bottom: 30px;">
            <h1 style="color: #28a745; margin: 0; font-size: 24px; font-weight: 600;">
                🎓 Course Enrollment Successful!
            </h1>
            <p style="color: #6c757d; margin: 10px 0 0 0; font-size: 16px;">
                Welcome to TIITVT - You have successfully enrolled in your course
            </p>
        </div>

        <div
            style="background-color: #d4edda; padding: 20px; border-radius: 8px; margin-bottom: 25px; border: 2px solid #c3e6cb;">
            <p style="margin: 0; font-size: 16px; color: #155724; font-weight: 600;">
                Dear <strong>{{ $studentName }}</strong>,
            </p>
        </div>

        <div style="margin-bottom: 25px;">
            <p style="font-size: 16px; color: #333; line-height: 1.6; margin-bottom: 15px;">
                Congratulations! You have successfully enrolled in your course at <strong>TIITVT</strong>. We're
                excited to have you join our learning community and begin your educational journey.
            </p>

            <div
                style="background-color: #f8f9fa; border: 2px solid #28a745; padding: 20px; border-radius: 6px; margin-bottom: 20px;">
                <h3 style="color: #155724; margin: 0 0 15px 0; font-size: 18px;">🎓 Course Enrollment Details</h3>

                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                    <span style="color: #495057; font-weight: 600;">TIITVT Reg No:</span>
                    <span style="color: #28a745; font-weight: 600; font-size: 18px;">{{ $tiitvtRegNo }}</span>
                </div>

                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                    <span style="color: #495057; font-weight: 600;">Course:</span>
                    <span style="color: #495057;">{{ $courseName }}</span>
                </div>

                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                    <span style="color: #495057; font-weight: 600;">Center:</span>
                    <span style="color: #495057;">{{ $centerName }}</span>
                </div>

                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                    <span style="color: #495057; font-weight: 600;">Enrollment Date:</span>
                    <span style="color: #495057;">{{ $enrollmentDate }}</span>
                </div>

                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                    <span style="color: #495057; font-weight: 600;">Course Fees:</span>
                    <span style="color: #28a745; font-weight: 600;">₹{{ number_format($courseFees, 2) }}</span>
                </div>

                @if ($downPayment > 0)
                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                        <span style="color: #495057; font-weight: 600;">Down Payment:</span>
                        <span style="color: #28a745; font-weight: 600;">₹{{ number_format($downPayment, 2) }}</span>
                    </div>
                @endif

                @if ($noOfInstallments > 0)
                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                        <span style="color: #495057; font-weight: 600;">Installments:</span>
                        <span style="color: #495057;">{{ $noOfInstallments }} monthly payments</span>
                    </div>

                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                        <span style="color: #495057; font-weight: 600;">Monthly Amount:</span>
                        <span style="color: #495057;">₹{{ number_format($monthlyInstallment, 2) }}</span>
                    </div>
                @endif
            </div>

            <div
                style="background-color: #e7f3ff; border: 1px solid #b3d9ff; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
                <p style="margin: 0; color: #0056b3; font-weight: 600; text-align: center;">
                    🎯 Your enrollment is now active! You can start your learning journey.
                </p>
            </div>

            <p style="font-size: 16px; color: #333; line-height: 1.6; margin-bottom: 15px;">
                <strong>Important Information:</strong>
            </p>

            <ul style="color: #333; line-height: 1.6; margin-bottom: 20px; padding-left: 20px;">
                <li style="margin-bottom: 8px;">📚 <strong>Course Access:</strong> Your course materials will be available
                    in your student portal</li>
                <li style="margin-bottom: 8px;">📅 <strong>Class Schedule:</strong> Check your batch time and schedule
                    regularly</li>
                <li style="margin-bottom: 8px;">💰 <strong>Payment Schedule:</strong> Ensure timely payment of installments
                    to avoid any delays</li>
                <li style="margin-bottom: 8px;">📞 <strong>Support:</strong> Our team is here to help you succeed</li>
            </ul>

            @if ($noOfInstallments > 0)
                <div
                    style="background-color: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
                    <h3 style="color: #856404; margin: 0 0 10px 0; font-size: 16px;">💡 Payment Reminder</h3>
                    <p style="margin: 0; color: #856404; font-size: 14px;">
                        <strong>Important:</strong> Please ensure timely payment of your monthly installments.
                        You will receive reminders before each due date. Late payments may affect your course access.
                    </p>
                </div>
            @endif
        </div>

        <div style="background-color: #f8f9fa; padding: 20px; border-radius: 8px; border: 1px solid #dee2e6;">
            <h3 style="color: #495057; margin: 0 0 15px 0; font-size: 18px;">📧 Next Steps</h3>
            <p style="margin: 0 0 10px 0; color: #495057; font-size: 14px;">
                <strong>Student Portal:</strong> <a href="{{ config('app.url') }}"
                    style="color: #007bff;">{{ config('app.url') }}</a>
            </p>
            <p style="margin: 0 0 10px 0; color: #495057; font-size: 14px;">
                <strong>Support Email:</strong> <a
                    href="mailto:{{ config('app.mail.support.address', 'support@tiitvt.com') }}"
                    style="color: #007bff;">{{ config('app.mail.support.address', 'support@tiitvt.com') }}</a>
            </p>
            <p style="margin: 0; color: #495057; font-size: 14px;">
                <strong>Emergency Contact:</strong> Available 24/7 through our support channels
            </p>
        </div>

        <div style="text-align: center; margin-top: 30px;">
            <p style="color: #6c757d; font-size: 14px; margin: 0;">
                Welcome to the TIITVT family! We're excited to be part of your success story.
            </p>
            <p style="color: #6c757d; font-size: 14px; margin: 5px 0 0 0;">
                Best regards,<br><strong>The TIITVT Team</strong>
            </p>
        </div>
    </div>
@endsection
