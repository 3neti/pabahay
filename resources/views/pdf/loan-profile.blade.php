<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Loan Profile - {{ $loanProfile->reference_code }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #3b82f6;
            padding-bottom: 10px;
        }
        .header h1 {
            color: #3b82f6;
            margin: 0;
        }
        .section {
            margin-bottom: 20px;
        }
        .section-title {
            background-color: #f3f4f6;
            padding: 8px;
            font-weight: bold;
            border-left: 4px solid #3b82f6;
            margin-bottom: 10px;
        }
        .row {
            display: flex;
            margin-bottom: 8px;
        }
        .label {
            font-weight: bold;
            width: 250px;
        }
        .value {
            flex: 1;
        }
        .status-qualified {
            color: #10b981;
            font-weight: bold;
        }
        .status-not-qualified {
            color: #ef4444;
            font-weight: bold;
        }
        .footer {
            margin-top: 40px;
            padding-top: 10px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            font-size: 10px;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Loan Profile Report</h1>
        <p>Reference Code: <strong>{{ $loanProfile->reference_code }}</strong></p>
        <p>Generated: {{ now()->format('F j, Y g:i A') }}</p>
    </div>

    @if($loanProfile->borrower_name || $loanProfile->borrower_email)
    <div class="section">
        <div class="section-title">Borrower Information</div>
        @if($loanProfile->borrower_name)
        <div class="row">
            <div class="label">Borrower Name:</div>
            <div class="value">{{ $loanProfile->borrower_name }}</div>
        </div>
        @endif
        @if($loanProfile->borrower_email)
        <div class="row">
            <div class="label">Email:</div>
            <div class="value">{{ $loanProfile->borrower_email }}</div>
        </div>
        @endif
        @if(isset($loanProfile->inputs['age']))
        <div class="row">
            <div class="label">Age:</div>
            <div class="value">{{ $loanProfile->inputs['age'] }} years</div>
        </div>
        @endif
        @if(isset($loanProfile->inputs['monthly_gross_income']))
        <div class="row">
            <div class="label">Monthly Gross Income:</div>
            <div class="value">₱{{ number_format($loanProfile->inputs['monthly_gross_income'], 2) }}</div>
        </div>
        @endif
    </div>
    @endif

    <div class="section">
        <div class="section-title">Loan Details</div>
        <div class="row">
            <div class="label">Lending Institution:</div>
            <div class="value">
                @switch($loanProfile->lending_institution)
                    @case('hdmf') HDMF (Pag-IBIG) @break
                    @case('rcbc') RCBC Savings Bank @break
                    @case('cbc') China Banking Corporation @break
                    @default {{ strtoupper($loanProfile->lending_institution) }}
                @endswitch
            </div>
        </div>
        <div class="row">
            <div class="label">Total Contract Price:</div>
            <div class="value">₱{{ number_format($loanProfile->total_contract_price, 2) }}</div>
        </div>
        @if(isset($loanProfile->computation['interest_rate']))
        <div class="row">
            <div class="label">Interest Rate:</div>
            <div class="value">{{ number_format($loanProfile->computation['interest_rate'] * 100, 2) }}%</div>
        </div>
        @endif
        @if(isset($loanProfile->computation['balance_payment_term']))
        <div class="row">
            <div class="label">Loan Term:</div>
            <div class="value">{{ $loanProfile->computation['balance_payment_term'] }} years</div>
        </div>
        @endif
    </div>

    <div class="section">
        <div class="section-title">Computation Results</div>
        @if(isset($loanProfile->computation['monthly_amortization']))
        <div class="row">
            <div class="label">Monthly Amortization:</div>
            <div class="value" style="font-size: 14px; font-weight: bold; color: #10b981;">
                ₱{{ number_format($loanProfile->computation['monthly_amortization'], 2) }}
            </div>
        </div>
        @endif
        @if(isset($loanProfile->computation['loanable_amount']))
        <div class="row">
            <div class="label">Loanable Amount:</div>
            <div class="value">₱{{ number_format($loanProfile->computation['loanable_amount'], 2) }}</div>
        </div>
        @endif
        <div class="row">
            <div class="label">Required Equity:</div>
            <div class="value">₱{{ number_format($loanProfile->required_equity, 2) }}</div>
        </div>
        @if(isset($loanProfile->computation['cash_out']))
        <div class="row">
            <div class="label">Cash Out:</div>
            <div class="value">₱{{ number_format($loanProfile->computation['cash_out'], 2) }}</div>
        </div>
        @endif
        @if(isset($loanProfile->computation['miscellaneous_fees']))
        <div class="row">
            <div class="label">Miscellaneous Fees:</div>
            <div class="value">₱{{ number_format($loanProfile->computation['miscellaneous_fees'], 2) }}</div>
        </div>
        @endif
    </div>

    <div class="section">
        <div class="section-title">Qualification Assessment</div>
        <div class="row">
            <div class="label">Qualification Status:</div>
            <div class="value {{ $loanProfile->qualified ? 'status-qualified' : 'status-not-qualified' }}">
                {{ $loanProfile->qualified ? 'QUALIFIED' : 'NOT QUALIFIED' }}
            </div>
        </div>
        <div class="row">
            <div class="label">Reason:</div>
            <div class="value">{{ $loanProfile->reason }}</div>
        </div>
        @if(!$loanProfile->qualified && $loanProfile->income_gap > 0)
        <div class="row">
            <div class="label">Income Gap:</div>
            <div class="value">₱{{ number_format($loanProfile->income_gap, 2) }}</div>
        </div>
        @endif
        @if(!$loanProfile->qualified && $loanProfile->suggested_down_payment_percent)
        <div class="row">
            <div class="label">Suggested Down Payment %:</div>
            <div class="value">{{ number_format($loanProfile->suggested_down_payment_percent * 100, 2) }}%</div>
        </div>
        @endif
    </div>

    <div class="footer">
        <p>This is a computer-generated document. No signature is required.</p>
        <p>Generated by Pabahay Mortgage Calculator System</p>
    </div>
</body>
</html>
