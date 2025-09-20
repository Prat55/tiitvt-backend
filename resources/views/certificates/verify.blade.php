<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate Verification - TIITVT</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .verification-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .verification-header {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }

        .logo {
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 20px;
            font-weight: 700;
        }

        .verification-title {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .verification-subtitle {
            font-size: 16px;
            opacity: 0.9;
        }

        .verification-content {
            padding: 40px;
        }

        .status-badge {
            display: inline-block;
            background: #10b981;
            color: white;
            padding: 8px 20px;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 30px;
        }

        .certificate-details {
            background: #f8fafc;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
        }

        .detail-label {
            font-size: 12px;
            color: #64748b;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }

        .detail-value {
            font-size: 16px;
            color: #1e293b;
            font-weight: 500;
        }

        .student-name {
            font-size: 24px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 20px;
            text-align: center;
            padding: 20px 0;
            border-top: 2px solid #e2e8f0;
            border-bottom: 2px solid #e2e8f0;
        }

        .verification-info {
            background: #fef3c7;
            border: 1px solid #f59e0b;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .verification-info h3 {
            color: #92400e;
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .verification-info p {
            color: #92400e;
            font-size: 14px;
            line-height: 1.5;
        }

        .qr-section {
            text-align: center;
            margin: 30px 0;
        }

        .qr-code {
            width: 150px;
            height: 150px;
            background: #f8f9fa;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            color: #64748b;
        }

        .verification-footer {
            background: #f8fafc;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #e2e8f0;
        }

        .footer-text {
            color: #64748b;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .footer-link {
            color: #3b82f6;
            text-decoration: none;
            font-weight: 500;
        }

        .footer-link:hover {
            text-decoration: underline;
        }

        .print-button {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 20px;
            transition: background 0.2s;
        }

        .print-button:hover {
            background: #2563eb;
        }

        @media (max-width: 768px) {
            .verification-container {
                margin: 10px;
                border-radius: 12px;
            }

            .verification-header,
            .verification-content {
                padding: 20px;
            }

            .detail-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <div class="verification-container">
        <div class="verification-header">
            <div class="logo">TIITVT</div>
            <h1 class="verification-title">Certificate Verification</h1>
            <p class="verification-subtitle">Technical Institute of Information Technology & Vocational Training</p>
        </div>

        <div class="verification-content">
            <div class="status-badge">✓ Certificate Verified</div>

            <div class="verification-info">
                <h3>Certificate Status: Valid</h3>
                <p>This certificate has been successfully verified and is authentic. The student has completed the
                    course requirements and is eligible for this certification.</p>
            </div>

            <div class="student-name">{{ $certificate->student->full_name ?? 'Student Name' }}</div>

            <div class="certificate-details">
                <div class="detail-grid">
                    <div class="detail-item">
                        <span class="detail-label">Certificate Number</span>
                        <span class="detail-value">{{ $certificate->certificate_number ?? 'N/A' }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Registration Number</span>
                        <span class="detail-value">{{ $certificate->student->tiitvt_reg_no ?? 'N/A' }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Course</span>
                        <span class="detail-value">{{ $certificate->course->name ?? 'N/A' }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Center</span>
                        <span class="detail-value">{{ $certificate->student->center->name ?? 'N/A' }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Issue Date</span>
                        <span class="detail-value">{{ $certificate->issued_on->format('F d, Y') ?? 'N/A' }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Status</span>
                        <span class="detail-value">{{ ucfirst($certificate->status ?? 'Active') }}</span>
                    </div>
                </div>
            </div>

            @if (isset($certificate->qr_token))
                <div class="qr-section">
                    <div class="qr-code">
                        <div>
                            <div style="font-size: 10px; margin-bottom: 5px;">VERIFICATION</div>
                            <div style="font-size: 8px;">QR Code</div>
                            <div style="font-size: 6px; margin-top: 5px;">{{ substr($certificate->qr_token, 0, 12) }}...
                            </div>
                        </div>
                    </div>
                    <p style="color: #64748b; font-size: 12px;">Certificate verification QR code</p>
                </div>
            @endif

            <button class="print-button" onclick="window.print()">Print Verification</button>
        </div>

        <div class="verification-footer">
            <p class="footer-text">This certificate has been verified on {{ now()->format('F d, Y \a\t g:i A') }}</p>
            <p class="footer-text">
                For more information, visit
                <a href="{{ config('app.url') }}" class="footer-link">{{ config('app.url') }}</a>
            </p>
        </div>
    </div>
</body>

</html>
