<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Receipt - {{ $receiptNumber }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #000;
            background: #fff;
        }

        .receipt-container {
            width: 100%;
            max-width: 210mm;
            /* A4 width */
            margin: 0 auto;
            padding: 10mm;
        }

        .receipt-row {
            display: flex;
            width: 100%;
            min-height: 50vh;
            border: 1px solid #000;
        }

        .receipt-copy {
            width: 50%;
            padding: 8mm;
            border-right: 2px solid #000;
            position: relative;
        }

        .receipt-copy:last-child {
            border-right: none;
        }

        .center-header {
            text-align: center;
            margin-bottom: 15px;
        }

        .center-name {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .center-address {
            font-size: 11px;
            font-weight: bold;
            text-align: left;
            margin-bottom: 15px;
        }

        .receipt-type-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .payment-type {
            font-size: 12px;
            font-weight: bold;
        }

        .copy-label {
            font-size: 14px;
            font-weight: bold;
        }

        .separator {
            border-top: 2px solid #000;
            margin: 10px 0;
        }

        .receipt-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .receipt-number {
            font-weight: bold;
        }

        .receipt-date {
            font-size: 11px;
        }

        .payment-acknowledgment {
            text-align: left;
            margin-bottom: 15px;
            font-size: 11px;
            line-height: 1.6;
        }

        .payment-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .payment-table td {
            padding: 6px 8px;
            border: 1px solid #000;
        }

        .payment-table td:first-child {
            font-weight: bold;
            width: 50%;
        }

        .payment-table td:last-child {
            text-align: right;
            width: 50%;
        }

        .signature-section {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
            margin-bottom: 20px;
        }

        .seal-section,
        .signatory-section {
            width: 45%;
            text-align: center;
        }

        .seal-label,
        .signatory-label {
            font-weight: bold;
            margin-top: 40px;
            border-top: 1px solid #000;
            padding-top: 5px;
            display: inline-block;
            min-width: 100px;
        }

        .footer-note {
            position: absolute;
            bottom: 10px;
            left: 8mm;
            right: 8mm;
            font-weight: bold;
            font-size: 10px;
            text-align: center;
            padding-top: 10px;
            border-top: 1px solid #000;
        }

        @media print {
            body {
                margin: 0;
                padding: 0;
            }

            .receipt-container {
                padding: 0;
                max-width: 100%;
            }

            .receipt-row {
                page-break-inside: avoid;
            }
        }
    </style>
</head>

<body>
    <div class="receipt-container">
        <div class="receipt-row">
            <!-- Student Copy -->
            <div class="receipt-copy">
                <div class="center-header">
                    <div class="center-name">{{ $center->name }}</div>
                </div>
                <div class="center-address">{{ $centerAddress }}</div>

                <div class="receipt-type-header">
                    <div class="payment-type">Cash | Cheque Receipt</div>
                    <div class="copy-label">Student Copy</div>
                </div>

                <div class="separator"></div>

                <div class="receipt-info">
                    <div class="receipt-number"><strong>Receipt No:</strong> {{ $receiptNumber }}</div>
                    <div class="receipt-date">Date:
                        {{ $installment->paid_date ? $installment->paid_date->format('d/m/Y') : now()->format('d/m/Y') }}
                    </div>
                </div>

                <div class="payment-acknowledgment">
                    <p>
                        Received with thanks from {{ $studentTitle }} <strong>{{ $studentName }}</strong> a sum of
                        <strong>₹{{ number_format($currentPaymentAmount, 2) }}</strong>
                        (<strong>{{ $amountInWords }} Rupees Only</strong>)
                        {{ $paymentType }} payment by
                        <strong>{{ ucfirst($paymentMethod) }}</strong>
                        on account of course <strong>{{ $course?->name ?? 'N/A' }}</strong>
                        @if ($paymentMethod === 'cheque' && $installment->cheque_number)
                            , Cheque No: <strong>{{ $installment->cheque_number }}</strong>
                            @if ($installment->withdrawn_date)
                                Drawn at {{ $installment->withdrawn_date->format('d/m/Y') }}
                            @endif
                        @endif
                        .
                    </p>
                </div>

                <table class="payment-table">
                    <tr>
                        <td>TOTAL FEES</td>
                        <td>₹{{ number_format($totalFees, 2) }}</td>
                    </tr>
                    <tr>
                        <td>PREVIOUS PAID</td>
                        <td>₹{{ number_format($totalPreviousPaid, 2) }}</td>
                    </tr>
                    <tr>
                        <td>FEES PAID</td>
                        <td>₹{{ number_format($currentPaymentAmount, 2) }}</td>
                    </tr>
                    <tr>
                        <td>BALANCE</td>
                        <td>₹{{ number_format($balanceAmount, 2) }}</td>
                    </tr>
                </table>

                <div class="signature-section">
                    <div class="seal-section">
                        <div class="seal-label">Center Seal</div>
                    </div>
                    <div class="signatory-section">
                        <div class="signatory-label">Authorized Signatory</div>
                    </div>
                </div>

                <div class="footer-note">
                    <strong>Cheque are subject to realisation. Fees once paid will not be refunded in any
                        condition.</strong>
                </div>
            </div>

            <!-- Center Copy -->
            <div class="receipt-copy">
                <div class="center-header">
                    <div class="center-name">{{ $center->name }}</div>
                </div>
                <div class="center-address">{{ $centerAddress }}</div>

                <div class="receipt-type-header">
                    <div class="payment-type">Cash | Cheque Receipt</div>
                    <div class="copy-label">Center Copy</div>
                </div>

                <div class="separator"></div>

                <div class="receipt-info">
                    <div class="receipt-number"><strong>Receipt No:</strong> {{ $receiptNumber }}</div>
                    <div class="receipt-date">Date:
                        {{ $installment->paid_date ? $installment->paid_date->format('d/m/Y') : now()->format('d/m/Y') }}
                    </div>
                </div>

                <div class="payment-acknowledgment">
                    <p>
                        Received with thanks from {{ $studentTitle }} <strong>{{ $studentName }}</strong> a sum of
                        <strong>₹{{ number_format($currentPaymentAmount, 2) }}</strong>
                        (<strong>{{ $amountInWords }} Rupees Only</strong>)
                        {{ $paymentType }} payment by
                        <strong>{{ ucfirst($paymentMethod) }}</strong>
                        on account of course <strong>{{ $course?->name ?? 'N/A' }}</strong>
                        @if ($paymentMethod === 'cheque' && $installment->cheque_number)
                            , Cheque No: <strong>{{ $installment->cheque_number }}</strong>
                            @if ($installment->withdrawn_date)
                                Drawn at {{ $installment->withdrawn_date->format('d/m/Y') }}
                            @endif
                        @endif
                        .
                    </p>
                </div>

                <table class="payment-table">
                    <tr>
                        <td>TOTAL FEES</td>
                        <td>₹{{ number_format($totalFees, 2) }}</td>
                    </tr>
                    <tr>
                        <td>PREVIOUS PAID</td>
                        <td>₹{{ number_format($totalPreviousPaid, 2) }}</td>
                    </tr>
                    <tr>
                        <td>FEES PAID</td>
                        <td>₹{{ number_format($currentPaymentAmount, 2) }}</td>
                    </tr>
                    <tr>
                        <td>BALANCE</td>
                        <td>₹{{ number_format($balanceAmount, 2) }}</td>
                    </tr>
                </table>

                <div class="signature-section">
                    <div class="seal-section">
                        <div class="seal-label">Center Seal</div>
                    </div>
                    <div class="signatory-section">
                        <div class="signatory-label">Authorized Signatory</div>
                    </div>
                </div>

                <div class="footer-note">
                    <strong>Cheque are subject to realisation. Fees once paid will not be refunded in any
                        condition.</strong>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
