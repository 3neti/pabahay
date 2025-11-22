<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Mortgage Computation Results</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #2563eb;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .content {
            background-color: #f9fafb;
            padding: 20px;
            border: 1px solid #e5e7eb;
        }
        .section {
            background-color: white;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 6px;
            border: 1px solid #e5e7eb;
        }
        .section h3 {
            margin-top: 0;
            color: #1f2937;
            font-size: 16px;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 8px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #f3f4f6;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            color: #6b7280;
            font-size: 14px;
        }
        .info-value {
            font-weight: 600;
            color: #111827;
            font-size: 14px;
        }
        .highlight {
            background-color: #dbeafe;
            padding: 15px;
            border-radius: 6px;
            margin: 15px 0;
            border-left: 4px solid #2563eb;
        }
        .highlight-success {
            background-color: #d1fae5;
            border-left-color: #10b981;
        }
        .highlight-warning {
            background-color: #fee2e2;
            border-left-color: #ef4444;
        }
        .reference-code {
            background-color: #f3f4f6;
            padding: 12px;
            border-radius: 6px;
            text-align: center;
            margin: 15px 0;
            font-family: 'Courier New', monospace;
            font-size: 18px;
            font-weight: bold;
            letter-spacing: 2px;
        }
        .footer {
            text-align: center;
            color: #6b7280;
            font-size: 12px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1 style="margin: 0; font-size: 24px;">Mortgage Computation Results</h1>
        <p style="margin: 5px 0 0 0; opacity: 0.9;">{{ $loanProfile->lending_institution_name ?? strtoupper($loanProfile->lending_institution) }}</p>
    </div>

    <div class="content">
        @if($loanProfile->borrower_name)
        <p>Dear {{ $loanProfile->borrower_name }},</p>
        @else
        <p>Hello,</p>
        @endif

        <p>Thank you for using our mortgage calculator. Below are your computation results:</p>

        <div class="reference-code">
            Reference Code: {{ $loanProfile->reference_code }}
        </div>

        <p style="font-size: 12px; color: #6b7280; text-align: center; margin-top: -10px;">
            Save this code to retrieve your calculation later
        </p>

        <!-- Qualification Status -->
        <div class="highlight {{ $loanProfile->qualification['qualifies'] ? 'highlight-success' : 'highlight-warning' }}">
            <strong style="font-size: 16px;">
                {{ $loanProfile->qualification['qualifies'] ? '✓ Loan Qualified' : '✗ Not Qualified' }}
            </strong>
            <p style="margin: 5px 0 0 0;">{{ $loanProfile->qualification['reason'] }}</p>
        </div>

        <!-- Key Figures -->
        <div class="section">
            <h3>Key Figures</h3>
            <div class="info-row">
                <span class="info-label">Monthly Amortization</span>
                <span class="info-value">₱{{ number_format($loanProfile->monthly_amortization, 2) }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Loan Term</span>
                <span class="info-value">{{ $loanProfile->balance_payment_term }} years</span>
            </div>
            <div class="info-row">
                <span class="info-label">Loanable Amount</span>
                <span class="info-value">₱{{ number_format($loanProfile->loanable_amount, 2) }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Required Equity</span>
                <span class="info-value">₱{{ number_format($loanProfile->required_equity, 2) }}</span>
            </div>
            @if(!$loanProfile->qualification['qualifies'])
            <div class="info-row">
                <span class="info-label">Income Gap</span>
                <span class="info-value" style="color: #dc2626;">₱{{ number_format($loanProfile->income_gap, 2) }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Suggested Down Payment</span>
                <span class="info-value">{{ number_format($loanProfile->percent_down_payment_remedy * 100, 2) }}%</span>
            </div>
            @endif
        </div>

        <!-- Property & Buyer Details -->
        <div class="section">
            <h3>Property & Buyer Details</h3>
            <div class="info-row">
                <span class="info-label">Total Contract Price</span>
                <span class="info-value">₱{{ number_format($loanProfile->total_contract_price, 2) }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Buyer Age</span>
                <span class="info-value">{{ $loanProfile->age }} years</span>
            </div>
            <div class="info-row">
                <span class="info-label">Monthly Gross Income</span>
                <span class="info-value">₱{{ number_format($loanProfile->monthly_gross_income, 2) }}</span>
            </div>
            @if($loanProfile->co_borrower_age)
            <div class="info-row">
                <span class="info-label">Co-Borrower Age</span>
                <span class="info-value">{{ $loanProfile->co_borrower_age }} years</span>
            </div>
            @endif
            @if($loanProfile->co_borrower_income)
            <div class="info-row">
                <span class="info-label">Co-Borrower Income</span>
                <span class="info-value">₱{{ number_format($loanProfile->co_borrower_income, 2) }}</span>
            </div>
            @endif
        </div>

        <!-- Loan Details -->
        <div class="section">
            <h3>Loan Details</h3>
            <div class="info-row">
                <span class="info-label">Interest Rate</span>
                <span class="info-value">{{ number_format($loanProfile->interest_rate * 100, 2) }}%</span>
            </div>
            <div class="info-row">
                <span class="info-label">Down Payment</span>
                <span class="info-value">{{ number_format($loanProfile->percent_down_payment * 100, 2) }}%</span>
            </div>
            <div class="info-row">
                <span class="info-label">Miscellaneous Fees</span>
                <span class="info-value">₱{{ number_format($loanProfile->miscellaneous_fees, 2) }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Cash Out</span>
                <span class="info-value">₱{{ number_format($loanProfile->cash_out, 2) }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Monthly Disposable Income</span>
                <span class="info-value">₱{{ number_format($loanProfile->monthly_disposable_income, 2) }}</span>
            </div>
        </div>

        <p style="margin-top: 20px; font-size: 14px;">
            You can retrieve this calculation anytime by visiting our mortgage calculator and entering your reference code: <strong>{{ $loanProfile->reference_code }}</strong>
        </p>

        <div class="footer">
            <p>This is an automated email. Please do not reply to this message.</p>
            <p>&copy; {{ date('Y') }} Pabahay. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
