@php
    $customer = $invoice->customer;

    $subscriberName = trim(implode(' ', array_filter([
        $customer?->first_name,
        $customer?->middle_name,
        $customer?->last_name,
    ])));

    $backUrl = auth()->user()?->role === 'customer'
        ? route('customer.billing.show', $invoice->id)
        : route('admin.invoices.show', $invoice->id);
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Receipt {{ $receipt->receipt_number }} | Rincomm CMS</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 36px 16px;
            background: #f3f6f6;
            color: #1f2937;
            font-family: Arial, Helvetica, sans-serif;
            line-height: 1.5;
        }

        .container {
            max-width: 760px;
            margin: 0 auto;
        }

        .actions {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 20px;
        }

        .button {
            display: inline-block;
            border: 1px solid #008080;
            border-radius: 6px;
            padding: 10px 16px;
            color: #008080;
            background: white;
            text-decoration: none;
            font-size: 13px;
            font-weight: bold;
            cursor: pointer;
        }

        .button-primary {
            background: #008080;
            color: white;
        }

        .receipt {
            overflow: hidden;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            background: white;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
        }

        .header {
            padding: 32px;
            background: #008080;
            color: white;
        }

        .brand {
            margin: 0;
            font-size: 26px;
            font-weight: bold;
        }

        .subtitle {
            margin: 6px 0 0;
            font-size: 13px;
            opacity: 0.9;
        }

        .body {
            padding: 32px;
        }

        .heading {
            margin: 0 0 6px;
            font-size: 21px;
            color: #111827;
        }

        .receipt-number {
            margin: 0 0 26px;
            color: #6b7280;
            font-size: 13px;
            overflow-wrap: anywhere;
        }

        .status {
            display: inline-block;
            margin-bottom: 24px;
            border-radius: 999px;
            padding: 6px 12px;
            background: #ecfdf5;
            color: #047857;
            font-size: 12px;
            font-weight: bold;
        }

        .section {
            margin-bottom: 25px;
        }

        .section-title {
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 9px;
            color: #008080;
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px 24px;
            padding-top: 18px;
        }

        .label {
            display: block;
            margin-bottom: 5px;
            color: #6b7280;
            font-size: 12px;
        }

        .value {
            display: block;
            color: #111827;
            font-size: 14px;
            font-weight: bold;
            overflow-wrap: anywhere;
        }

        .amount-box {
            margin-top: 30px;
            border: 1px solid #cce5e5;
            border-radius: 8px;
            padding: 20px;
            background: #f0fafa;
            text-align: center;
        }

        .amount-label {
            color: #516565;
            font-size: 13px;
        }

        .amount {
            margin-top: 6px;
            color: #008080;
            font-size: 32px;
            font-weight: bold;
        }

        .footer {
            border-top: 1px solid #e5e7eb;
            padding: 22px 32px;
            color: #6b7280;
            font-size: 11px;
            text-align: center;
        }

        @media (max-width: 520px) {
            .header,
            .body {
                padding: 22px;
            }

            .details {
                grid-template-columns: 1fr;
            }

            .amount {
                font-size: 27px;
            }
        }

        @media print {
            @page {
                size: A4;
                margin: 15mm;
            }

            body {
                padding: 0;
                background: white;
            }

            .actions {
                display: none !important;
            }

            .receipt {
                border-radius: 0;
                box-shadow: none;
            }

            .header,
            .amount-box {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <nav class="actions" aria-label="Receipt actions">
            <a href="{{ $backUrl }}" class="button">
                Back to Invoice
            </a>

            <button
                type="button"
                class="button button-primary"
                onclick="window.print()">
                Print / Save as PDF
            </button>
        </nav>

        <main class="receipt">
            <header class="header">
                <h1 class="brand">RINCOMM</h1>
                <p class="subtitle">
                    Internet Service Management System
                </p>
            </header>

            <div class="body">
                <h2 class="heading">Electronic Payment Receipt</h2>

                <p class="receipt-number">
                    Receipt No. {{ $receipt->receipt_number }}
                </p>

                <span class="status">
                    Payment Completed
                </span>

                <section class="section">
                    <h3 class="section-title">
                        Subscriber Information
                    </h3>

                    <div class="details">
                        <div>
                            <span class="label">Subscriber Name</span>
                            <span class="value">
                                {{ $subscriberName !== '' ? $subscriberName : 'Subscriber' }}
                            </span>
                        </div>

                        <div>
                            <span class="label">Customer Code</span>
                            <span class="value">
                                {{ $customer?->customer_code ?? 'Not recorded' }}
                            </span>
                        </div>
                    </div>
                </section>

                <section class="section">
                    <h3 class="section-title">
                        Payment Information
                    </h3>

                    <div class="details">
                        <div>
                            <span class="label">Invoice Number</span>
                            <span class="value">
                                {{ $invoice->invoice_number }}
                            </span>
                        </div>

                        <div>
                            <span class="label">Payment Reference</span>
                            <span class="value">
                                {{ $payment->payment_reference }}
                            </span>
                        </div>

                        <div>
                            <span class="label">Payment Method</span>
                            <span class="value">
                                {{ $receipt->payment_method === 'gcash' ? 'GCash' : ucfirst($receipt->payment_method) }}
                            </span>
                        </div>

                        <div>
                            <span class="label">Payment Date</span>
                            <span class="value">
                                {{ $payment->paid_at?->format('M d, Y h:i A') ?? 'Not recorded' }}
                            </span>
                        </div>

                        <div>
                            <span class="label">Receipt Issued</span>
                            <span class="value">
                                {{ $receipt->issued_at?->format('M d, Y h:i A') ?? 'Not recorded' }}
                            </span>
                        </div>

                        <div>
                            <span class="label">Issued By</span>
                            <span class="value">
                                {{ $receipt->issued_by ?? 'System' }}
                            </span>
                        </div>
                    </div>
                </section>

                <section class="amount-box">
                    <div class="amount-label">
                        Total Payment Received
                    </div>

                    <div class="amount">
                        &#8369;{{ number_format((float) $receipt->amount_paid, 2) }}
                    </div>
                </section>
            </div>

            <footer class="footer">
                Generated by Rincomm CMS from a completed payment record.
                Please retain this document for your records.
            </footer>
        </main>
    </div>
</body>
</html>