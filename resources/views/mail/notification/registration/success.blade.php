@extends('mail.layout.app')

@section('content')
    <div style="max-width: 600px; margin: 0 auto; padding: 20px; background-color: #ffffff;">
        <div style="text-align: center; margin-bottom: 30px;">
            <h1
                style="color: #1a365d; margin: 0; font-size: 24px; font-weight: 600; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
                Course Enrollment Successful!
            </h1>
            <p style="color: #6c757d; margin: 10px 0 0 0; font-size: 16px;">
                Welcome to TIITVT - You have successfully enrolled in your course
            </p>
        </div>

        <div>
            <p style="margin: 0; font-size: 16px; color: #000; font-weight: 600;">
                Dear <strong>{{ $studentName }}</strong>,
            </p>
        </div>

        <div style="margin-bottom: 25px;">
            <p style="font-size: 16px; color: #333; line-height: 1.6; margin-bottom: 15px;">
                Congratulations! You have successfully enrolled in your course at <strong>TIITVT</strong>. We're
                excited to have you join our learning community and begin your educational journey.
            </p>

            <div
                style="background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%); border: 2px solid #667eea; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);">
                <h3 style="color: #1a365d; margin: 0 0 15px 0; font-size: 18px; font-weight: 700;">Course Enrollment
                    Details</h3>

                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                    <span style="color: #4a5568; font-weight: 600;">TIITVT Reg No:</span>
                    <span style="color: #667eea; font-weight: 700; font-size: 18px;">{{ $tiitvtRegNo }}</span>
                </div>

                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                    <span style="color: #4a5568; font-weight: 600;">Course:</span>
                    <span style="color: #2d3748; font-weight: 500;">{{ $courseName }}</span>
                </div>

                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                    <span style="color: #4a5568; font-weight: 600;">Center:</span>
                    <span style="color: #2d3748; font-weight: 500;">{{ $centerName }}</span>
                </div>

                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                    <span style="color: #4a5568; font-weight: 600;">Enrollment Date:</span>
                    <span style="color: #2d3748; font-weight: 500;">{{ $enrollmentDate }}</span>
                </div>

                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                    <span style="color: #4a5568; font-weight: 600;">Course Fees:</span>
                    <span
                        style="color: #d69e2e; font-weight: 700; font-size: 16px;">₹{{ number_format($courseFees, 2) }}</span>
                </div>

                @if ($downPayment > 0)
                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                        <span style="color: #4a5568; font-weight: 600;">Down Payment:</span>
                        <span style="color: #d69e2e; font-weight: 700;">₹{{ number_format($downPayment, 2) }}</span>
                    </div>
                @endif

                @if ($noOfInstallments > 0)
                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                        <span style="color: #4a5568; font-weight: 600;">Installments:</span>
                        <span style="color: #2d3748; font-weight: 500;">{{ $noOfInstallments }} monthly payments</span>
                    </div>

                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                        <span style="color: #4a5568; font-weight: 600;">Monthly Amount:</span>
                        <span style="color: #2d3748; font-weight: 500;">₹{{ number_format($monthlyInstallment, 2) }}</span>
                    </div>
                @endif
            </div>

            <p style="font-size: 16px; color: #2d3748; line-height: 1.6; margin-bottom: 15px; font-weight: 600;">
                <strong>Important Information:</strong>
            </p>

            <ul style="color: #4a5568; line-height: 1.6; margin-bottom: 20px; padding-left: 20px;">
                <li style="margin-bottom: 8px;">
                    <strong style="color: #2d3748;">Class Schedule:</strong>
                    Check your batch time and schedule regularly
                </li>

                <li style="margin-bottom: 8px;">
                    <strong style="color: #2d3748;">Payment Schedule:</strong>
                    Ensure timely payment of installments to avoid any delays
                </li>

                <li style="margin-bottom: 8px;">
                    <strong style="color: #2d3748;">Support:</strong>
                    Our team is here to help you succeed
                </li>
            </ul>

            @if ($noOfInstallments > 0)
                <div
                    style="background: linear-gradient(135deg, #fef5e7 0%, #fed7aa 100%); border: 1px solid #f6ad55; padding: 15px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);">
                    <h3 style="color: #c05621; margin: 0 0 10px 0; font-size: 16px; font-weight: 700;">Payment Reminder
                    </h3>
                    <p style="margin: 0; color: #c05621; font-size: 14px; font-weight: 500;">
                        <strong>Important:</strong> Please ensure timely payment of your monthly installments.
                        You will receive reminders before each due date. Late payments may affect your course access.
                    </p>
                </div>
            @endif
        </div>

        @if (isset($qrCodeUrl) && $qrCodeUrl)
            <div
                style="text-align: center; margin: 30px 0; padding: 20px; background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%); border: 2px solid #667eea; border-radius: 12px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);">
                <h3 style="color: #1a365d; margin: 0 0 15px 0; font-size: 18px; font-weight: 700;">Your Student QR Code</h3>
                <p style="color: #4a5568; font-size: 14px; margin: 0 0 20px 0; line-height: 1.5;">
                    Scan this QR code to access your student information and verify your enrollment status.
                </p>

                <div
                    style="display: inline-block; padding: 15px; background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);">
                    <img src="{{ $qrCodeUrl }}" alt="Student QR Code"
                        style="max-width: 200px; height: auto; display: block;" />
                </div>

                <p style="color: #667eea; font-size: 12px; margin: 15px 0 0 0; font-weight: 600;">
                    Keep this QR code safe - you'll need it for verification purposes
                </p>
            </div>
        @endif

        <div style="text-align: center; margin-top: 30px;">
            <p style="color: #4a5568; font-size: 14px; margin: 0; font-weight: 500;">
                Welcome to the TIITVT family! We're excited to be part of your success story.
            </p>

            <p style="color: #4a5568; font-size: 14px; margin: 5px 0 0 0;">
                Best regards,<br><strong style="color: #2d3748; font-weight: 700;">The TIITVT Team</strong>
            </p>
        </div>
    </div>
@endsection
