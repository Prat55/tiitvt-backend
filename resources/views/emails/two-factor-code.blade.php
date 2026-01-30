<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <div style="background-color: #f8f9fa; padding: 20px; border-radius: 8px;">
        <h2 style="color: #333; margin-bottom: 10px;">Two-Factor Authentication Code</h2>
        <p style="color: #666; font-size: 14px; margin-bottom: 20px;">Hi {{ $user->name }},</p>

        <p style="color: #666; font-size: 14px; margin-bottom: 20px;">
            Your two-factor authentication code is:
        </p>

        <div
            style="background-color: #ffffff; border: 2px solid #e0e0e0; border-radius: 8px; padding: 20px; text-align: center; margin-bottom: 20px;">
            <p style="font-size: 32px; font-weight: bold; color: #000; letter-spacing: 5px; margin: 0;">
                {{ $code }}
            </p>
        </div>

        <p style="color: #666; font-size: 14px; margin-bottom: 10px;">
            This code will expire in 10 minutes.
        </p>

        <p style="color: #999; font-size: 12px; margin-bottom: 20px;">
            If you didn't request this code, please ignore this email or contact support immediately.
        </p>

        <hr style="border: none; border-top: 1px solid #e0e0e0; margin: 20px 0;">

        <p style="color: #999; font-size: 12px; text-align: center;">
            This is an automated email. Please do not reply to this address.
        </p>
    </div>
</div>
