<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Invoice - {{ $receiptNumber }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 18mm 16mm;
        }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            font-size: 13px;
            color: #222;
            background: #f8f9fa;
        }

        .invoice-box {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.07);
            padding: 32px 36px;
            margin: 0 auto;
            max-width: 700px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
        }

        .header .logo {
            font-size: 28px;
            font-weight: bold;
            color: #007bff;
            letter-spacing: 1px;
        }

        .header .invoice-title {
            font-size: 20px;
            font-weight: 600;
            color: #333;
            text-align: right;
        }

        .meta-table {
            width: 100%;
            margin-bottom: 24px;
        }

        .meta-table td {
            padding: 4px 0;
            font-size: 14px;
        }

        .meta-table .label {
            color: #888;
            font-weight: 500;
            width: 120px;
        }

        .meta-table .value {
            color: #222;
            font-weight: 600;
        }

        .divider {
            border-top: 2px solid #007bff;
            margin: 18px 0 24px 0;
        }

        .section-title {
            font-size: 15px;
            font-weight: 600;
            color: #007bff;
            margin-bottom: 8px;
        }

        .info-table {
            width: 100%;
            margin-bottom: 18px;
        }

        .info-table td {
            padding: 2px 0;
            font-size: 13px;
        }

        .amount-table {
            width: 100%;
            border-collapse: collapse;
            margin: 24px 0;
        }

        .amount-table th,
        .amount-table td {
            border: 1px solid #e0e0e0;
            padding: 10px 14px;
            font-size: 14px;
        }

        .amount-table th {
            background: #f1f7ff;
            color: #007bff;
            font-weight: 600;
        }

        .amount-table td {
            font-weight: 500;
        }

        .amount-table .total-row td {
            background: #f9fafb;
            font-size: 16px;
            font-weight: bold;
            color: #007bff;
        }

        .footer {
            margin-top: 32px;
            text-align: center;
            color: #888;
            font-size: 12px;
        }

        .computer-generated {
            margin-top: 18px;
            color: #d9534f;
            font-weight: bold;
            font-size: 15px;
            text-align: right;
        }
    </style>
</head>

<body>
    <div class="invoice-box">
        <div class="header">
            <div class="logo">
                {{ $center->name ?? 'TIITVT' }}
                <div style="font-size:12px; font-weight:normal; color:#555; margin-top:2px;">
                    {{ $centerAddress }}
                </div>
            </div>
            <div class="invoice-title">INVOICE</div>
        </div>
        <table class="meta-table">
            <tr>
                <td class="label">Invoice No:</td>
                <td class="value">{{ $receiptNumber }}</td>
            </tr>
            <tr>
                <td class="label">Date:</td>
                <td class="value">{{ $paymentDate ? $paymentDate->format('d/m/Y') : now()->format('d/m/Y') }}</td>
            </tr>
        </table>
        <div class="divider"></div>
        <div class="section-title">Billed To</div>
        <table class="info-table">
            <tr>
                <td><strong>{{ $studentName }}</strong></td>
            </tr>
        </table>
        <div class="section-title">Course(s)</div>
        <table class="info-table">
            <tr>
                <td>{{ $courses->pluck('name')->implode(', ') ?: 'N/A' }}</td>
            </tr>
        </table>
        <div class="divider"></div>
        <table class="amount-table">
            <tr>
                <th>Description</th>
                <th>Amount (₹)</th>
            </tr>
            <tr>
                <td>Total Fees</td>
                <td>{{ number_format($totalFees, 2) }}</td>
            </tr>
            <tr>
                <td>Previous Paid</td>
                <td>{{ number_format($totalPreviousPaidWithDown ?? $totalPreviousPaid, 2) }}</td>
            </tr>
            <tr>
                <td>Fees Paid (This Installment)</td>
                <td>{{ number_format($currentPaymentAmount, 2) }}</td>
            </tr>
            <tr class="total-row">
                <td>Balance</td>
                <td>{{ number_format($balanceAmount, 2) }}</td>
            </tr>
        </table>
        <div class="section-title">Payment Details</div>
        <table class="info-table">
            <tr>
                <td>Payment Method:</td>
                <td>{{ ucfirst($paymentMethod) }}</td>
            </tr>
            <tr>
                <td>Payment Type:</td>
                <td>{{ ucfirst($paymentType) }}</td>
            </tr>
            <tr>
                <td>Amount in Words:</td>
                <td>{{ $amountInWords }} Rupees Only</td>
            </tr>
        </table>
        <div class="computer-generated"
            style="text-align:center; margin-top:40px; margin-bottom:8px; font-size:15px; color:#d9534f; font-weight:bold;">
            COMPUTER GENERATED INVOICE
        </div>
        <div class="footer">
            This is a computer generated invoice. No signature required.<br>
            Fees once paid will not be refunded in any condition.
        </div>
    </div>
</body>

</html>
