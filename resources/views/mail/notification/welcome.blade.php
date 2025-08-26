@extends('mail.layout.app')

@section('content')
    <div style="max-width: 600px; margin: 0 auto; padding: 20px; background-color: #ffffff;">
        <div style="text-align: center; margin-bottom: 30px;">
            <h1 style="color: #28a745; margin: 0; font-size: 24px; font-weight: 600;">
                🎉 Welcome to TIITVT!
            </h1>
            <p style="color: #6c757d; margin: 10px 0 0 0; font-size: 16px;">
                Your educational journey begins here
            </p>
        </div>

        <div style="background-color: #d4edda; padding: 20px; border-radius: 8px; margin-bottom: 25px; border: 2px solid #c3e6cb;">
            <p style="margin: 0; font-size: 16px; color: #155724; font-weight: 600;">
                Dear <strong>{{ $studentName }}</strong>,
            </p>
        </div>

        <div style="margin-bottom: 25px;">
            <p style="font-size: 16px; color: #333; line-height: 1.6; margin-bottom: 15px;">
                Welcome to <strong>TIITVT</strong>! We're thrilled to have you join our community of learners and innovators.
            </p>

            <div style="background-color: #f8f9fa; border: 1px solid #dee2e6; padding: 20px; border-radius: 6px; margin-bottom: 20px;">
                <h3 style="color: #495057; margin: 0 0 15px 0; font-size: 18px;">🎓 Your Course Information</h3>
                
                @if(isset($courseInfo) && !empty($courseInfo))
                    @if(isset($courseInfo['name']))
                        <p style="margin: 0 0 10px 0; color: #495057;">
                            <strong>Course:</strong> {{ $courseInfo['name'] }}
                        </p>
                    @endif
                    
                    @if(isset($courseInfo['enrollment_date']))
                        <p style="margin: 0 0 10px 0; color: #495057;">
                            <strong>Enrollment Date:</strong> {{ $courseInfo['enrollment_date'] }}
                        </p>
                    @endif
                    
                    @if(isset($courseInfo['instructor']))
                        <p style="margin: 0 0 10px 0; color: #495057;">
                            <strong>Instructor:</strong> {{ $courseInfo['instructor'] }}
                        </p>
                    @endif
                @else
                    <p style="margin: 0; color: #6c757d; font-style: italic;">
                        Course details will be provided by your instructor.
                    </p>
                @endif
            </div>

            <p style="font-size: 16px; color: #333; line-height: 1.6; margin-bottom: 15px;">
                Here's what you can expect from your TIITVT experience:
            </p>

            <ul style="color: #333; line-height: 1.6; margin-bottom: 20px; padding-left: 20px;">
                <li style="margin-bottom: 8px;">📚 <strong>Quality Education:</strong> Industry-relevant curriculum designed by experts</li>
                <li style="margin-bottom: 8px;">👨‍🏫 <strong>Expert Instructors:</strong> Learn from professionals with real-world experience</li>
                <li style="margin-bottom: 8px;">💻 <strong>Practical Learning:</strong> Hands-on projects and real-world applications</li>
                <li style="margin-bottom: 8px;">🤝 <strong>Community Support:</strong> Connect with fellow students and alumni</li>
                <li style="margin-bottom: 8px;">🎯 <strong>Career Growth:</strong> Skills that directly translate to job opportunities</li>
            </ul>

            <div style="background-color: #e7f3ff; border: 1px solid #b3d9ff; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
                <p style="margin: 0; color: #0056b3; font-weight: 600; text-align: center;">
                    🚀 Ready to get started? Your first class details will be sent shortly!
                </p>
            </div>
        </div>

        <div style="background-color: #f8f9fa; padding: 20px; border-radius: 8px; border: 1px solid #dee2e6;">
            <h3 style="color: #495057; margin: 0 0 15px 0; font-size: 18px;">📧 Stay Connected</h3>
            <p style="margin: 0 0 10px 0; color: #495057; font-size: 14px;">
                <strong>Student Portal:</strong> <a href="{{ config('app.url') }}" style="color: #007bff;">{{ config('app.url') }}</a>
            </p>
            <p style="margin: 0 0 10px 0; color: #495057; font-size: 14px;">
                <strong>Support Email:</strong> <a href="mailto:{{ config('app.mail.support.address', 'support@tiitvt.com') }}" style="color: #007bff;">{{ config('app.mail.support.address', 'support@tiitvt.com') }}</a>
            </p>
            <p style="margin: 0; color: #495057; font-size: 14px;">
                <strong>Emergency Contact:</strong> Available 24/7 through our support channels
            </p>
        </div>

        <div style="text-align: center; margin-top: 30px;">
            <p style="color: #6c757d; font-size: 14px; margin: 0;">
                We're excited to be part of your success story!
            </p>
            <p style="color: #6c757d; font-size: 14px; margin: 5px 0 0 0;">
                Best regards,<br><strong>The TIITVT Team</strong>
            </p>
        </div>
    </div>
@endsection
