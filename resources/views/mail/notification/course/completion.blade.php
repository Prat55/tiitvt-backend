@extends('mail.layout.app')

@section('content')
    <div style="max-width: 600px; margin: 0 auto; padding: 20px; background-color: #ffffff;">
        <div style="text-align: center; margin-bottom: 30px;">
            <h1 style="color: #ff6b35; margin: 0; font-size: 24px; font-weight: 600;">
                🎓 Congratulations!
            </h1>
            <p style="color: #6c757d; margin: 10px 0 0 0; font-size: 16px;">
                You've successfully completed your course
            </p>
        </div>

        <div style="background-color: #fff3cd; padding: 20px; border-radius: 8px; margin-bottom: 25px; border: 2px solid #ffeaa7;">
            <p style="margin: 0; font-size: 16px; color: #856404; font-weight: 600;">
                Dear <strong>{{ $studentName }}</strong>,
            </p>
        </div>

        <div style="margin-bottom: 25px;">
            <p style="font-size: 16px; color: #333; line-height: 1.6; margin-bottom: 15px;">
                <strong>Congratulations!</strong> You have successfully completed your course at TIITVT. This is a significant achievement that represents your dedication, hard work, and commitment to learning.
            </p>

            <div style="background-color: #f8f9fa; border: 2px solid #ff6b35; padding: 20px; border-radius: 6px; margin-bottom: 20px;">
                <h3 style="color: #d63384; margin: 0 0 15px 0; font-size: 18px;">🏆 Course Completion Details</h3>
                
                @if(isset($courseDetails) && !empty($courseDetails))
                    @if(isset($courseDetails['name']))
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                            <span style="color: #495057; font-weight: 600;">Course Name:</span>
                            <span style="color: #495057;">{{ $courseDetails['name'] }}</span>
                        </div>
                    @endif
                    
                    @if(isset($courseDetails['completion_date']))
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                            <span style="color: #495057; font-weight: 600;">Completion Date:</span>
                            <span style="color: #495057;">{{ $courseDetails['completion_date'] }}</span>
                        </div>
                    @endif
                    
                    @if(isset($courseDetails['grade']))
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                            <span style="color: #495057; font-weight: 600;">Final Grade:</span>
                            <span style="color: #28a745; font-weight: 600; font-size: 18px;">{{ $courseDetails['grade'] }}</span>
                        </div>
                    @endif
                    
                    @if(isset($courseDetails['certificate_number']))
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                            <span style="color: #495057; font-weight: 600;">Certificate Number:</span>
                            <span style="color: #495057; font-family: monospace;">{{ $courseDetails['certificate_number'] }}</span>
                        </div>
                    @endif
                @else
                    <p style="margin: 0; color: #6c757d; font-style: italic;">
                        Course completion details will be provided by your instructor.
                    </p>
                @endif
            </div>

            <div style="background-color: #e7f3ff; border: 1px solid #b3d9ff; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
                <p style="margin: 0; color: #0056b3; font-weight: 600; text-align: center;">
                    🎯 You've earned your certificate! It will be available for download shortly.
                </p>
            </div>

            <p style="font-size: 16px; color: #333; line-height: 1.6; margin-bottom: 15px;">
                What you've accomplished:
            </p>

            <ul style="color: #333; line-height: 1.6; margin-bottom: 20px; padding-left: 20px;">
                <li style="margin-bottom: 8px;">📚 <strong>Knowledge Acquisition:</strong> Mastered the course curriculum and learning objectives</li>
                <li style="margin-bottom: 8px;">💻 <strong>Practical Skills:</strong> Developed hands-on skills through projects and assignments</li>
                <li style="margin-bottom: 8px;">🤝 <strong>Professional Growth:</strong> Enhanced your professional capabilities and marketability</li>
                <li style="margin-bottom: 8px;">🎯 <strong>Goal Achievement:</strong> Successfully reached your educational milestone</li>
            </ul>

            <p style="font-size: 16px; color: #333; line-height: 1.6; margin-bottom: 15px;">
                Next steps in your journey:
            </p>

            <ul style="color: #333; line-height: 1.6; margin-bottom: 20px; padding-left: 20px;">
                <li style="margin-bottom: 8px;">📜 <strong>Download Certificate:</strong> Your digital certificate will be available in your student portal</li>
                <li style="margin-bottom: 8px;">🔗 <strong>Update Resume:</strong> Add your new qualification to your professional profile</li>
                <li style="margin-bottom: 8px;">🌐 <strong>Network:</strong> Connect with fellow graduates and industry professionals</li>
                <li style="margin-bottom: 8px;">📚 <strong>Continue Learning:</strong> Consider advanced courses or specializations</li>
            </ul>
        </div>

        <div style="background-color: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
            <h3 style="color: #155724; margin: 0 0 10px 0; font-size: 16px;">🌟 Alumni Benefits</h3>
            <ul style="color: #155724; margin: 0; padding-left: 20px; font-size: 14px;">
                <li style="margin-bottom: 5px;">Access to alumni network and events</li>
                <li style="margin-bottom: 5px;">Discounts on future courses and programs</li>
                <li style="margin-bottom: 5px;">Career development resources and support</li>
                <li style="margin-bottom: 5px;">Continued access to learning materials</li>
            </ul>
        </div>

        <div style="background-color: #f8f9fa; padding: 20px; border-radius: 8px; border: 1px solid #dee2e6;">
            <h3 style="color: #495057; margin: 0 0 15px 0; font-size: 18px;">📧 Stay Connected</h3>
            <p style="margin: 0 0 10px 0; color: #495057; font-size: 14px;">
                <strong>Student Portal:</strong> <a href="{{ config('app.url') }}" style="color: #007bff;">{{ config('app.url') }}</a>
            </p>
            <p style="margin: 0 0 10px 0; color: #495057; font-size: 14px;">
                <strong>Alumni Services:</strong> <a href="mailto:{{ config('app.mail.support.address', 'alumni@tiitvt.com') }}" style="color: #007bff;">{{ config('app.mail.support.address', 'alumni@tiitvt.com') }}</a>
            </p>
            <p style="margin: 0; color: #495057; font-size: 14px;">
                <strong>Career Support:</strong> Available for all graduates
            </p>
        </div>

        <div style="text-align: center; margin-top: 30px;">
            <p style="color: #6c757d; font-size: 14px; margin: 0;">
                Your success is our success! We're proud of what you've accomplished.
            </p>
            <p style="color: #6c757d; font-size: 14px; margin: 5px 0 0 0;">
                Best regards,<br><strong>The TIITVT Team</strong>
            </p>
        </div>
    </div>
@endsection
