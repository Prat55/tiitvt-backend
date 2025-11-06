@extends('mail.layout.app')

@section('content')
    <div style="max-width: 600px; margin: 0 auto; padding: 20px; background-color: #ffffff;">
        <!-- Header -->
        <div style="text-align: center; margin-bottom: 30px;">
            <h1 style="color: #2e7d32; margin: 0; font-size: 24px; font-weight: 600;">
                Payment Receipt
            </h1>
            <p style="color: #666; margin: 5px 0 0 0; font-size: 14px;">
                {{ date('d F Y') }}
            </p>
        </div>

        <!-- Payment Acknowledgment -->
        <div
            style="background-color: #f1f8e9; padding: 25px; border-radius: 8px; margin-bottom: 30px; border: 2px solid #4caf50;">
            <p style="margin: 0; font-size: 16px; color: #2e7d32; line-height: 1.6; text-align: center;">
                <strong>Received with thanks from Mr./Ms./Mrs. {{ $student->first_name }} {{ $student->surname }} sum of Rs.
                    {{ number_format($amount, 2) }} (Rupees {{ $amount_in_words ?? 'in words' }}) as {{ $payment_type ?? 'full' }} payment
                    by {{ ucfirst($payment_method ?? 'cash') }} on account of {{ $student->course->name ?? 'course name' }}</strong>
            </p>
        </div>

        <!-- Payment Details Table -->
        <div style="margin-bottom: 30px;">
            <h3 style="color: #333; font-size: 18px; margin-bottom: 15px; text-align: center;">Payment Details</h3>
            <table
                style="width: 100%; border-collapse: collapse; background-color: #f8f9fa; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <thead>
                    <tr style="background-color: #2e7d32;">
                        <th style="padding: 15px; text-align: left; color: white; font-weight: 600; border: none;">
                            Description</th>
                        <th style="padding: 15px; text-align: right; color: white; font-weight: 600; border: none;">Amount
                            (₹)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr style="border-bottom: 1px solid #e0e0e0;">
                        <td style="padding: 15px; font-weight: 600; color: #333; border: none;">
                            <span style="margin-left: 5px">Total Course Fees</span>
                        </td>
                        <td style="padding: 15px; text-align: right; color: #333; border: none;">
                            <span style="margin-left: 5px">₹{{ number_format($total_fees ?? 0, 2) }}</span>
                        </td>
                    </tr>
                    <tr style="border-bottom: 1px solid #e0e0e0;">
                        <td style="padding: 15px; font-weight: 600; color: #333; border: none;">
                            <span style="margin-left: 5px">Previously Paid (Before This Payment)</span>
                        </td>
                        <td style="padding: 15px; text-align: right; color: #666; border: none;">
                            <span style="margin-left: 5px">₹{{ number_format($previous_paid ?? 0, 2) }}</span>
                        </td>
                    </tr>
                    <tr style="border-bottom: 1px solid #e0e0e0; background-color: #e8f5e9;">
                        <td style="padding: 15px; font-weight: 700; color: #2e7d32; border: none; font-size: 16px;">
                            <span style="margin-left: 5px">Current Payment (Amount Paid Now)</span>
                        </td>
                        <td style="padding: 15px; text-align: right; color: #2e7d32; border: none; font-weight: 700; font-size: 16px;">
                            <span style="margin-left: 5px">₹{{ number_format($current_payment ?? $amount ?? 0, 2) }}</span>
                        </td>
                    </tr>
                    <tr style="border-bottom: 1px solid #e0e0e0;">
                        <td style="padding: 15px; font-weight: 600; color: #333; border: none;">
                            <span style="margin-left: 5px">Total Paid (After This Payment)</span>
                        </td>
                        <td style="padding: 15px; text-align: right; color: #2e7d32; border: none; font-weight: 600;">
                            <span style="margin-left: 5px">₹{{ number_format($total_paid_after ?? (($previous_paid ?? 0) + ($current_payment ?? $amount ?? 0)), 2) }}</span>
                        </td>
                    </tr>
                    <tr style="background-color: #ffebee;">
                        <td style="padding: 15px; font-weight: 700; color: #d32f2f; border: none; font-size: 16px;">
                            <span style="margin-left: 5px">Balance Amount (Remaining)</span>
                        </td>
                        <td
                            style="padding: 15px; text-align: right; color: #d32f2f; border: none; font-weight: 700; font-size: 16px;">
                            <span style="margin-left: 5px">₹{{ number_format($balance_amount ?? 0, 2) }}</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Student Information -->
        <div style="background-color: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 30px;">
            <h4 style="color: #333; font-size: 16px; margin-bottom: 15px;">Student Information</h4>
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 8px 0; font-weight: 600; color: #333; width: 30%;">Student ID:</td>
                    <td style="padding: 8px 0; color: #666;">{{ $student->tiitvt_reg_no }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; font-weight: 600; color: #333;">Student Name:</td>
                    <td style="padding: 8px 0; color: #666;">{{ $student->first_name }} {{ $student->surname }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; font-weight: 600; color: #333;">Course:</td>
                    <td style="padding: 8px 0; color: #666;">{{ $student->course->name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; font-weight: 600; color: #333;">Center:</td>
                    <td style="padding: 8px 0; color: #666;">{{ $student->center->name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; font-weight: 600; color: #333;">Payment Date:</td>
                    <td style="padding: 8px 0; color: #666;">{{ date('d F Y') }}</td>
                </tr>
                @if (isset($payment_method) && $payment_method === 'cheque')
                    @if (isset($cheque_number))
                        <tr>
                            <td style="padding: 8px 0; font-weight: 600; color: #333;">Cheque Number:</td>
                            <td style="padding: 8px 0; color: #666;">{{ $cheque_number }}</td>
                        </tr>
                    @endif
                    @if (isset($withdrawn_date))
                        <tr>
                            <td style="padding: 8px 0; font-weight: 600; color: #333;">Withdrawn Date:</td>
                            <td style="padding: 8px 0; color: #666;">
                                {{ \Carbon\Carbon::parse($withdrawn_date)->format('d F Y') }}</td>
                        </tr>
                    @endif
                @endif
            </table>
        </div>

        <!-- Closing -->
        <div style="text-align: center; margin-top: 40px; padding-top: 20px; border-top: 2px solid #e0e0e0;">
            <p style="margin: 0; color: #333; font-size: 16px; font-weight: 600;">
                Best Regards,
            </p>
            <p style="margin: 10px 0 0 0; color: #666; font-size: 14px;">
                {{ getWebsiteSettings()->meta_author }} Team.
            </p>
            <p style="margin: 20px 0 0 0; color: #999; font-size: 12px;">
                © {{ date('Y') }} {{ getWebsiteSettings()->meta_author }}. All rights reserved.
            </p>
        </div>
    </div>
@endsection
