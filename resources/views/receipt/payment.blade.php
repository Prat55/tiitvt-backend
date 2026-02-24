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
            background: #f5f5f5;
            padding: 20px;
        }

        .print-header {
            width: 100%;
            margin: 0 auto 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #fff;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .website-name {
            font-size: 18px;
            font-weight: bold;
            color: #2c3e50;
        }

        .print-button {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: background 0.3s;
        }

        .print-button:hover {
            background: #0056b3;
        }

        .print-button:active {
            transform: scale(0.98);
        }

        .receipt-container {
            width: 100%;
            max-width: 900px;
            margin: 30px auto;
            padding: 20px;
            background: #fff;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }

        .receipt-row {
            display: flex;
            flex-direction: column;
            width: 100%;
            gap: 30px;
        }

        .receipt-copy {
            width: 100%;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 4px;
            position: relative;
            display: flex;
            flex-direction: column;
            background: #fff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .receipt-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 0;
        }

        .receipt-copy:last-child {
            border-bottom: none;
        }

        .center-header {
            text-align: center;
            margin-bottom: 15px;
            width: 100%;
        }

        .center-name {
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 8px;
            width: 100%;
        }

        .center-address {
            font-size: 12px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 15px;
            line-height: 1.5;
            width: 100%;
        }

        .receipt-type-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .payment-type {
            font-size: 16px;
            font-weight: bold;
        }

        .copy-label {
            font-size: 18px;
            font-weight: bold;
            text-align: right;
        }

        .separator {
            border-top: 2px solid #000;
            margin: 10px 0;
        }

        .receipt-info {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            margin-bottom: 15px;
            gap: 0;
        }

        .receipt-number {
            font-weight: bold;
            font-size: 14px;
            flex-shrink: 0;
        }

        .receipt-date {
            font-size: 14px;
            font-weight: bold;
            text-align: right;
            white-space: nowrap;
        }

        .payment-acknowledgment {
            text-align: left;
            margin-top: 20px;
            margin-bottom: 25px;
            font-size: 16px;
            line-height: 1.6;
        }

        .payment-acknowledgment p {
            margin: 0;
        }

        .payment-table {
            width: 70%;
            border-collapse: collapse;
            margin: 20px auto 20px auto;
        }

        .payment-table td {
            padding: 10px 12px;
            border: 1px solid #000;
            font-size: 13px;
            font-weight: bold;
        }

        .payment-table td:first-child {
            width: 50%;
        }

        .payment-table td:last-child {
            text-align: right;
            width: 50%;
            font-variant-numeric: tabular-nums;
        }

        .signature-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .seal-section,
        .signatory-section {
            width: 45%;
            text-align: center;
        }

        .signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 40px;
            margin-bottom: 20px;
        }

        .signature-table td {
            padding: 0;
            text-align: center;
            vertical-align: bottom;
            width: 50%;
            padding-top: 50px;
        }

        .seal-label,
        .signatory-label {
            font-weight: bold;
            margin-top: 0;
            border-top: 1px solid #000;
            padding-top: 5px;
            display: inline-block;
            min-width: 120px;
            font-size: 12px;
        }

        .footer-note {
            margin-top: 20px;
            margin-bottom: 0;
            font-weight: bold;
            font-size: 11px;
            text-align: center;
            padding-top: 10px;
            padding-bottom: 0;
            border-top: 1px solid #000;
            line-height: 1.5;
        }

        @media print {
            body {
                margin: 0;
                padding: 0;
                background: #fff;
            }

            .print-header {
                display: none;
            }

            .receipt-container {
                padding: 0;
                max-width: 100%;
                width: 100%;
                box-shadow: none;
                margin: 0;
                border-radius: 0;
            }

            .receipt-row {
                page-break-inside: avoid;
                width: 100%;
                height: 1073px;
                gap: 0;
                display: flex;
                flex-direction: column;
                align-items: center;
            }

            .receipt-copy {
                page-break-inside: avoid;
                flex: 0 0 510px;
                height: 510px;
                width: 85%;
                margin: 0 auto;
                padding: 8px 8px 4px 8px;
                border: none;
                border-bottom: 1px solid #000;
                border-radius: 0;
                box-shadow: none;
                overflow: hidden;
                display: flex;
                flex-direction: column;
            }

            .receipt-copy:last-child {
                flex: 0 0 471px;
                height: 471px;
                margin-top: 40px;
                border-bottom: none;
            }

            .center-header {
                margin-bottom: 4px;
                width: 100%;
            }

            .center-name {
                font-size: 24px;
                margin-bottom: 2px;
                width: 100%;
            }

            .center-address {
                font-size: 13px;
                margin-bottom: 4px;
                text-align: center;
                width: 100%;
            }

            .receipt-type-header {
                margin-bottom: 4px;
            }

            .payment-type {
                font-size: 15px;
            }

            .copy-label {
                font-size: 17px;
            }

            .separator {
                margin: 4px 0;
            }

            .receipt-info {
                margin-bottom: 4px;
            }

            .receipt-number {
                font-size: 15px;
            }

            .receipt-date {
                font-size: 15px;
            }

            .payment-acknowledgment {
                margin-top: 8px;
                margin-bottom: 10px;
                font-size: 15px;
                line-height: 1.4;
            }

            .payment-table {
                width: 70%;
                margin: 15px auto 5px auto;
            }

            .payment-table td {
                padding: 3px 5px;
                font-size: 13px;
            }

            .signature-table {
                margin-top: 45px;
                margin-bottom: 5px;
            }

            .signature-table td {
                padding-top: 30px;
            }

            .seal-label,
            .signatory-label {
                padding-top: 3px;
                min-width: 100px;
                font-size: 13px;
            }

            .footer-note {
                margin-top: 5px;
                font-size: 12px;
                padding-top: 3px;
                line-height: 1.3;
            }

            @page {
                size: A4;
                margin: 30px 10px 20px 10px;
            }
        }
    </style>
</head>

<body>
    <div class="print-header">
        <div class="website-name">{{ $websiteName ?? 'TIITVT' }}</div>
        <button class="print-button" onclick="window.print()">🖨️ Print Receipt</button>
    </div>
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
                    <div class="receipt-date"><strong>Date:</strong>
                        <strong>{{ isset($paymentDate) ? $paymentDate->format('d/m/Y') : (isset($installment) && $installment->paid_date ? $installment->paid_date->format('d/m/Y') : now()->format('d/m/Y')) }}</strong>
                    </div>
                </div>

                <div class="payment-acknowledgment">
                    <p>
                        Received with thanks from {{ $studentTitle }} <strong>{{ $studentName }}</strong> a sum of
                        <strong>₹{{ number_format($currentPaymentAmount, 2) }}</strong>
                        (<strong>{{ $amountInWords }} Rupees Only</strong>)
                        {{ $paymentType }} payment by
                        <strong>{{ ucfirst($paymentMethod) }}</strong>
                        on account of course <strong>{{ $courses->pluck('name')->implode(', ') ?: 'N/A' }}</strong>
                        @if (
                            $paymentMethod === 'cheque' &&
                                (isset($chequeNumber) ? $chequeNumber : isset($installment) && $installment->cheque_number))
                            , Cheque No: <strong>{{ $chequeNumber ?? $installment->cheque_number }}</strong>
                            @if (isset($withdrawnDate) ? $withdrawnDate : isset($installment) && $installment->withdrawn_date)
                                Drawn at {{ ($withdrawnDate ?? $installment->withdrawn_date)->format('d/m/Y') }}
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
                        <td>₹{{ number_format($totalPreviousPaidWithDown ?? $totalPreviousPaid, 2) }}</td>
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

                <table class="signature-table">
                    <tr>
                        <td>
                            <div class="seal-label">Center Seal</div>
                        </td>
                        <td>
                            <div class="signatory-label">Authorized Signatory</div>
                        </td>
                    </tr>
                </table>

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
                    <div class="receipt-date"><strong>Date:</strong>
                        <strong>{{ isset($paymentDate) ? $paymentDate->format('d/m/Y') : (isset($installment) && $installment->paid_date ? $installment->paid_date->format('d/m/Y') : now()->format('d/m/Y')) }}</strong>
                    </div>
                </div>

                <div class="payment-acknowledgment">
                    <p>
                        Received with thanks from {{ $studentTitle }} <strong>{{ $studentName }}</strong> a sum of
                        <strong>₹{{ number_format($currentPaymentAmount, 2) }}</strong>
                        (<strong>{{ $amountInWords }} Rupees Only</strong>)
                        {{ $paymentType }} payment by
                        <strong>{{ ucfirst($paymentMethod) }}</strong>
                        on account of course <strong>{{ $courses->pluck('name')->implode(', ') ?: 'N/A' }}</strong>
                        @if (
                            $paymentMethod === 'cheque' &&
                                (isset($chequeNumber) ? $chequeNumber : isset($installment) && $installment->cheque_number))
                            , Cheque No: <strong>{{ $chequeNumber ?? $installment->cheque_number }}</strong>
                            @if (isset($withdrawnDate) ? $withdrawnDate : isset($installment) && $installment->withdrawn_date)
                                Drawn at {{ ($withdrawnDate ?? $installment->withdrawn_date)->format('d/m/Y') }}
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
                        <td>₹{{ number_format($totalPreviousPaidWithDown ?? $totalPreviousPaid, 2) }}</td>
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

                <table class="signature-table">
                    <tr>
                        <td>
                            <div class="seal-label">Center Seal</div>
                        </td>
                        <td>
                            <div class="signatory-label">Authorized Signatory</div>
                        </td>
                    </tr>
                </table>

                <div class="footer-note">
                    <strong>Cheque are subject to realisation. Fees once paid will not be refunded in any
                        condition.</strong>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
