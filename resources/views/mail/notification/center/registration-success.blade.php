@extends('mail.layout.app')

@section('content')
    <div style="max-width: 600px; margin: 0 auto; padding: 20px; background-color: #ffffff;">
        <div style="text-align: center; margin-bottom: 30px;">
            <h1 style="color: #28a745; margin: 0; font-size: 24px; font-weight: 600;">
                🎉 Center Registration Successful!
            </h1>
            <p style="color: #6c757d; margin: 10px 0 0 0; font-size: 16px;">
                Welcome to TIITVT - Your center has been successfully registered
            </p>
        </div>

        <p style="margin: 0; font-size: 16px; color: #155724; font-weight: 600;">
            Dear <strong>{{ $centerOwnerName }}</strong>,
        </p>

        <div style="margin-bottom: 25px;">
            <p style="font-size: 16px; color: #333; line-height: 1.6; margin-bottom: 15px;">
                Congratulations! Your center <strong>{{ $centerName }}</strong> has been successfully registered with
                <strong>TIITVT</strong>.
                You are now an official partner in our educational network and can start enrolling students.
            </p>

            <div
                style="background-color: #f8f9fa; border: 2px solid #28a745; padding: 20px; border-radius: 6px; margin-bottom: 20px;">
                <h3 style="color: #155724; margin: 0 0 15px 0; font-size: 18px;">🏢 Center Registration Details</h3>

                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                    <span style="color: #495057; font-weight: 600;">Center ID:</span>
                    <span style="color: #28a745; font-weight: 600; font-size: 18px;">{{ $centerId }}</span>
                </div>

                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                    <span style="color: #495057; font-weight: 600;">Center Name:</span>
                    <span style="color: #495057;">{{ $centerName }}</span>
                </div>

                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                    <span style="color: #495057; font-weight: 600;">Owner Name:</span>
                    <span style="color: #495057;">{{ $centerOwnerName }}</span>
                </div>

                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                    <span style="color: #495057; font-weight: 600;">Email:</span>
                    <span style="color: #495057;">{{ $email }}</span>
                </div>

                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                    <span style="color: #495057; font-weight: 600;">Phone:</span>
                    <span style="color: #495057;">{{ $phone }}</span>
                </div>

                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                    <span style="color: #495057; font-weight: 600;">Address:</span>
                    <span style="color: #495057;">{{ $address }}, {{ $state }}, {{ $country }}</span>
                </div>

                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                    <span style="color: #495057; font-weight: 600;">Registration Date:</span>
                    <span style="color: #495057;">{{ $registrationDate }}</span>
                </div>
            </div>

            <div
                style="background-color: #e7f3ff; border: 1px solid #b3d9ff; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
                <p style="margin: 0; color: #0056b3; font-weight: 600; text-align: center;">
                    🎯 Your center is now active! You can start enrolling students and managing courses.
                </p>
            </div>

            <div
                style="background-color: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
                <h3 style="color: #856404; margin: 0 0 10px 0; font-size: 16px;">🔐 Login Credentials</h3>
                <p style="margin: 0 0 10px 0; color: #856404; font-size: 14px;">
                    <strong>Important:</strong> Please save these login credentials securely. You can change your password
                    after first login.
                </p>
                <div style="background-color: #f8f9fa; padding: 10px; border-radius: 4px; margin-top: 10px;">
                    <p style="margin: 0; color: #495057; font-size: 14px;">
                        <strong>Email:</strong> {{ $email }}<br>
                        <strong>Password:</strong> {{ $password }}
                    </p>
                </div>
            </div>

            <p style="font-size: 16px; color: #333; line-height: 1.6; margin-bottom: 15px;">
                <strong>Next Steps:</strong>
            </p>

            <ul style="color: #333; line-height: 1.6; margin-bottom: 20px; padding-left: 20px;">
                <li style="margin-bottom: 8px;">🔑 <strong>Login:</strong> Access your center dashboard using the
                    credentials above</li>
                <li style="margin-bottom: 8px;">👥 <strong>Student Enrollment:</strong> Start enrolling students for various
                    courses</li>
                <li style="margin-bottom: 8px;">📚 <strong>Course Management:</strong> Set up and manage your course
                    offerings</li>
                <li style="margin-bottom: 8px;">📊 <strong>Reports:</strong> Access detailed reports and analytics</li>
                <li style="margin-bottom: 8px;">📞 <strong>Support:</strong> Our team is here to help you succeed</li>
            </ul>
        </div>

        <div style="background-color: #f8f9fa; padding: 20px; border-radius: 8px; border: 1px solid #dee2e6;">
            <h3 style="color: #495057; margin: 0 0 15px 0; font-size: 18px;">📧 Important Links</h3>
            <p style="margin: 0 0 10px 0; color: #495057; font-size: 14px;">
                <strong>Center Dashboard:</strong> <a href="{{ config('app.url') }}/center/dashboard"
                    style="color: #007bff;">{{ config('app.url') }}/center/dashboard</a>
            </p>
            <p style="margin: 0 0 10px 0; color: #495057; font-size: 14px;">
                <strong>Support Email:</strong> <a
                    href="mailto:{{ config('app.mail.support.address', 'support@tiitvt.com') }}"
                    style="color: #007bff;">{{ config('app.mail.support.address', 'support@tiitvt.com') }}</a>
            </p>
            <p style="margin: 0; color: #495057; font-size: 14px;">
                <strong>Documentation:</strong> Available in your dashboard under "Help & Support"
            </p>
        </div>

        <div style="text-align: center; margin-top: 30px;">
            <p style="color: #6c757d; font-size: 14px; margin: 0;">
                Welcome to the TIITVT family! We're excited to partner with you in providing quality education.
            </p>
            <p style="color: #6c757d; font-size: 14px; margin: 5px 0 0 0;">
                Best regards,<br><strong>The TIITVT Team</strong>
            </p>
        </div>
    </div>
@endsection
