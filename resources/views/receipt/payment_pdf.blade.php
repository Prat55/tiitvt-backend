<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Payment Receipt - {{ $receiptNumber }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 14mm 12mm;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'Arial', sans-serif;
            font-size: 12px;
            color: #000;
            line-height: 1.4;
        }

        .page {
            width: 100%;
        }

        .receipt-copy {
            width: 100%;
            padding: 20px;
            margin-bottom: 12px;
            box-sizing: border-box;
            page-break-inside: avoid;
        }

        .copy-header {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .copy-header td {
            vertical-align: top;
        }

        .copy-type {
            text-align: right;
            font-weight: bold;
            white-space: nowrap;
        }

        .center-block {
            text-align: center;
        }

        .center-name {
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 6px;
        }

        .center-address {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .line {
            border-top: 2px solid #000;
            margin: 10px 0;
        }

        .meta {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .meta td {
            vertical-align: top;
            font-size: 14px;
        }

        .meta td:last-child {
            text-align: right;
        }

        .ack {
            margin-top: 20px;
            margin-bottom: 25px;
            font-size: 16px;
            line-height: 1.6;
            word-wrap: break-word;
        }

        .amount-table {
            width: 70%;
            border-collapse: collapse;
            margin: 20px auto;
        }

        .amount-table td {
            border: 1px solid #000;
            padding: 10px 12px;
            font-size: 13px;
            font-weight: bold;
        }

        .amount-table td:last-child {
            text-align: right;
            white-space: nowrap;
        }

        .sign-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 40px;
            margin-bottom: 20px;
        }

        .sign-table td {
            width: 50%;
            text-align: center;
            vertical-align: bottom;
            padding-top: 50px;
        }

        .sign-line {
            display: inline-block;
            border-top: 1px solid #000;
            padding-top: 5px;
            min-width: 120px;
            font-weight: bold;
            font-size: 12px;
        }

        .footer-note {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #000;
            text-align: center;
            font-size: 11px;
            font-weight: bold;
            line-height: 1.5;
        }
    </style>
</head>

<body>
    @php
        $paidDate = isset($paymentDate)
            ? $paymentDate->format('d/m/Y')
            : (isset($installment) && $installment->paid_date
                ? $installment->paid_date->format('d/m/Y')
                : now()->format('d/m/Y'));
        $courseNames = $courses->pluck('name')->implode(', ') ?: 'N/A';
    @endphp

    <div class="page">
        @foreach (['Student Copy', 'Center Copy'] as $copyLabel)
            <div class="receipt-copy">
                <table class="copy-header">
                    <tr>
                        <td>
                            <div class="center-block">
                                <div class="center-name">{{ $center->name ?? ($websiteName ?? 'TIITVT') }}</div>
                                <div class="center-address">{{ $centerAddress }}</div>
                                <div><strong>Cash | Cheque Receipt</strong></div>
                            </div>
                        </td>
                        <td class="copy-type">{{ $copyLabel }}</td>
                    </tr>
                </table>

                <div class="line"></div>

                <table class="meta">
                    <tr>
                        <td><strong>Receipt No:</strong> {{ $receiptNumber }}</td>
                        <td><strong>Date:</strong> {{ $paidDate }}</td>
                    </tr>
                </table>

                <div class="ack">
                    Received with thanks from {{ $studentTitle }} <strong>{{ $studentName }}</strong> a sum of
                    <strong>Rs. {{ number_format($currentPaymentAmount, 2) }}</strong>
                    (<strong>{{ $amountInWords }} Rupees Only</strong>)
                    as {{ $paymentType }} payment by
                    <strong>{{ ucfirst($paymentMethod) }}</strong> on account of course
                    <strong>{{ $courseNames }}</strong>.
                </div>

                <table class="amount-table">
                    <tr>
                        <td><strong>TOTAL FEES</strong></td>
                        <td>Rs. {{ number_format($totalFees, 2) }}</td>
                    </tr>
                    <tr>
                        <td><strong>PREVIOUS PAID</strong></td>
                        <td>Rs. {{ number_format($totalPreviousPaidWithDown ?? $totalPreviousPaid, 2) }}</td>
                    </tr>
                    <tr>
                        <td><strong>FEES PAID</strong></td>
                        <td>Rs. {{ number_format($currentPaymentAmount, 2) }}</td>
                    </tr>
                    <tr>
                        <td><strong>BALANCE</strong></td>
                        <td>Rs. {{ number_format($balanceAmount, 2) }}</td>
                    </tr>
                </table>

                <table class="sign-table">
                    <tr>
                        <td><span class="sign-line">Center Seal</span></td>
                        <td><span class="sign-line">Authorized Signatory</span></td>
                    </tr>
                </table>

                <div class="footer-note">
                    Cheque is subject to realization. Fees once paid will not be refunded in any condition.
                </div>
            </div>
        @endforeach
    </div>
</body>

</html>
