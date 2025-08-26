@extends('mail.layout.app')

@section('content')
    <div style="max-width: 600px; margin: 0 auto; padding: 20px; background-color: #ffffff;">
        <div style="text-align: center; margin-bottom: 30px;">
            <h1 style="color: #fd7e14; margin: 0; font-size: 24px; font-weight: 600;">
                🔧 System Maintenance Notice
            </h1>
            <p style="color: #6c757d; margin: 10px 0 0 0; font-size: 16px;">
                Important information about scheduled maintenance
            </p>
        </div>

        <div style="background-color: #fff3cd; padding: 20px; border-radius: 8px; margin-bottom: 25px; border: 2px solid #ffeaa7;">
            <p style="margin: 0; font-size: 16px; color: #856404; font-weight: 600;">
                Dear TIITVT User,
            </p>
        </div>

        <div style="margin-bottom: 25px;">
            <p style="font-size: 16px; color: #333; line-height: 1.6; margin-bottom: 15px;">
                We want to inform you about scheduled system maintenance that will temporarily affect our services. This maintenance is necessary to improve system performance, security, and reliability.
            </p>

            <div style="background-color: #f8f9fa; border: 2px solid #fd7e14; padding: 20px; border-radius: 6px; margin-bottom: 20px;">
                <h3 style="color: #d63384; margin: 0 0 15px 0; font-size: 18px;">📅 Maintenance Schedule</h3>
                
                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                    <span style="color: #495057; font-weight: 600;">Scheduled Time:</span>
                    <span style="color: #495057; font-weight: 600;">{{ $scheduledTime ?? 'To be determined' }}</span>
                </div>
                
                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                    <span style="color: #495057; font-weight: 600;">Duration:</span>
                    <span style="color: #495057;">Estimated 2-4 hours</span>
                </div>
                
                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                    <span style="color: #495057; font-weight: 600;">Status:</span>
                    <span style="color: #fd7e14; font-weight: 600;">Scheduled</span>
                </div>
            </div>

            <div style="background-color: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
                <h3 style="color: #856404; margin: 0 0 10px 0; font-size: 16px;">⚠️ What to Expect</h3>
                <ul style="color: #856404; margin: 0; padding-left: 20px; font-size: 14px;">
                    <li style="margin-bottom: 5px;">Temporary unavailability of the student portal</li>
                    <li style="margin-bottom: 5px;">Email notifications may be delayed</li>
                    <li style="margin-bottom: 5px;">File uploads and downloads will be unavailable</li>
                    <li style="margin-bottom: 5px;">Online assessments will be temporarily suspended</li>
                </ul>
            </div>

            <div style="background-color: #e7f3ff; border: 1px solid #b3d9ff; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
                <h3 style="color: #0056b3; margin: 0 0 10px 0; font-size: 16px;">💡 What We're Improving</h3>
                <ul style="color: #0056b3; margin: 0; padding-left: 20px; font-size: 14px;">
                    <li style="margin-bottom: 5px;">Enhanced system security and performance</li>
                    <li style="margin-bottom: 5px;">Improved user experience and interface</li>
                    <li style="margin-bottom: 5px;">Better data backup and recovery systems</li>
                    <li style="margin-bottom: 5px;">Updated features and functionality</li>
                </ul>
            </div>

            <p style="font-size: 16px; color: #333; line-height: 1.6; margin-bottom: 15px;">
                <strong>Maintenance Details:</strong>
            </p>

            <div style="background-color: #f8f9fa; border: 1px solid #dee2e6; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
                <p style="margin: 0; color: #495057; line-height: 1.6;">
                    {{ $maintenanceDetails ?? 'This maintenance includes system updates, security patches, and performance optimizations to ensure the best possible experience for all users.' }}
                </p>
            </div>

            <p style="font-size: 16px; color: #333; line-height: 1.6; margin-bottom: 15px;">
                We understand that this may cause some inconvenience, and we appreciate your patience. The maintenance is scheduled during off-peak hours to minimize disruption to your learning experience.
            </p>
        </div>

        <div style="background-color: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
            <h3 style="color: #155724; margin: 0 0 10px 0; font-size: 16px;">✅ After Maintenance</h3>
            <ul style="color: #155724; margin: 0; padding-left: 20px; font-size: 14px;">
                <li style="margin-bottom: 5px;">All services will be restored automatically</li>
                <li style="margin-bottom: 5px;">You'll receive a confirmation email when maintenance is complete</li>
                <li style="margin-bottom: 5px;">Any delayed notifications will be sent immediately</li>
                <li style="margin-bottom: 5px;">New features and improvements will be available</li>
            </ul>
        </div>

        <div style="background-color: #f8f9fa; padding: 20px; border-radius: 8px; border: 1px solid #dee2e6;">
            <h3 style="color: #495057; margin: 0 0 15px 0; font-size: 18px;">📧 Need Help?</h3>
            <p style="margin: 0 0 10px 0; color: #495057; font-size: 14px;">
                <strong>Support Email:</strong> <a href="mailto:{{ config('app.mail.support.address', 'support@tiitvt.com') }}" style="color: #007bff;">{{ config('app.mail.support.address', 'support@tiitvt.com') }}</a>
            </p>
            <p style="margin: 0 0 10px 0; color: #495057; font-size: 14px;">
                <strong>Emergency Contact:</strong> Available during maintenance for urgent issues
            </p>
            <p style="margin: 0; color: #495057; font-size: 14px;">
                <strong>Status Updates:</strong> Check our social media for real-time updates
            </p>
        </div>

        <div style="text-align: center; margin-top: 30px;">
            <p style="color: #6c757d; font-size: 14px; margin: 0;">
                Thank you for your understanding and patience.
            </p>
            <p style="color: #6c757d; font-size: 14px; margin: 5px 0 0 0;">
                Best regards,<br><strong>The TIITVT Technical Team</strong>
            </p>
        </div>
    </div>
@endsection
