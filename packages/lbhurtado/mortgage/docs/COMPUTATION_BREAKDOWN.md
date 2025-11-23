# Mortgage Computation Breakdown

This document provides a complete breakdown of how mortgage computations work in the system, with real examples and JSON responses.

## Example Scenario

**Inputs:**
- Lending Institution: RCBC
- Total Contract Price (TCP): ₱2,300,000
- Age: 30 years old
- Monthly Gross Income: ₱75,000
- Interest Rate: 8%
- Loan Term: 20 years (maximum for RCBC)

## Step-by-Step Calculation

### Step 1: Total Contract Price (TCP)
```
TCP = ₱2,300,000
```
This is the base price of the property before any fees.

### Step 2: Down Payment
```
Down Payment % = 10% (RCBC requirement)
Down Payment Amount = ₱2,300,000 × 10% = ₱230,000
```
**Paid upfront by buyer.**

### Step 3: Base Loan Amount
```
Base Loan Amount = TCP - Down Payment
Base Loan Amount = ₱2,300,000 - ₱230,000 = ₱2,070,000
```
This is the amount you need to borrow **before** adding fees.

### Step 4: Miscellaneous Fees (MF)
```
MF % = 8.5% (RCBC requirement)
MF Amount = TCP × 8.5%
MF Amount = ₱2,300,000 × 0.085 = ₱195,500
```
**NOT paid upfront - added to the loan.**

### Step 5: Total Amount Financed (Loanable Amount)
```
Total Amount Financed = Base Loan Amount + Miscellaneous Fees
Total Amount Financed = ₱2,070,000 + ₱195,500 = ₱2,265,500
```
**This is your actual loan amount** - what you're borrowing from the bank.

### Step 6: Total Property Cost
```
Total Property Cost = TCP + Miscellaneous Fees
Total Property Cost = ₱2,300,000 + ₱195,500 = ₱2,495,500
```
This is the **true cost** of the property including all fees.

### Step 7: Monthly Amortization
```
Monthly Amortization = PMT(rate, term, -loan)
Monthly Amortization = PMT(8%/12, 20×12, -₱2,265,500)
Monthly Amortization = ₱18,949.55
```
Calculated on the **Total Amount Financed**, not just the base loan.

## Complete Financial Summary

### Upfront Payments (Cash Out)
```
Down Payment:        ₱230,000
Required Equity:     ₱0 (if income sufficient)
Total Upfront:       ₱230,000
```

### Financed Amount
```
Base Loan:           ₱2,070,000
Miscellaneous Fees:  ₱195,500
Total Financed:      ₱2,265,500  ← Your loan
```

### Total Costs
```
TCP:                 ₱2,300,000
Miscellaneous Fees:  ₱195,500
Total Property Cost: ₱2,495,500  ← True property cost
```

### Monthly Obligations
```
Monthly Amortization: ₱18,949.55
Loan Term:            20 years (240 months)
Total Payments:       ₱4,547,892
Total Interest:       ₱2,282,392
```

## Backend API Response

The `/api/v1/mortgage/compute` endpoint returns all these values:

```json
{
  "tcp": 2300000,
  "down_payment_amount": 230000,
  "down_payment_percent": 0.1,
  "base_loan_amount": 2070000,
  "miscellaneous_fees": 195500,
  "percent_miscellaneous_fees": 0.085,
  "loanable_amount": 2265500,
  "total_property_cost": 2495500,
  "monthly_amortization": 18949.55,
  "balance_payment_term": 20,
  "interest_rate": 0.08
}
```

## Key Terminology

### Loanable Amount
**Backend meaning:** Total Amount Financed (includes MF)
- This is the amount the bank will lend you
- Includes Base Loan + Miscellaneous Fees
- This is what your monthly payment is calculated on

### Base Loan Amount
- TCP minus Down Payment only
- Does NOT include miscellaneous fees
- New field added to clarify the breakdown

### Miscellaneous Fees
- Always calculated on TCP (not base loan)
- Added to the loan and amortized
- NOT paid upfront

### Total Property Cost
- TCP + Miscellaneous Fees
- The true total cost of ownership
- Different from TCP alone

## UI Display Recommendations

The UI should display values in this order for clarity:

1. **Total Contract Price** - ₱2,300,000
2. **Down Payment** - ₱230,000 (10% of TCP, paid upfront)
3. **Base Loan Amount** - ₱2,070,000 (TCP - Down Payment)
4. **Miscellaneous Fees** - ₱195,500 (8.5% of TCP, added to loan)
5. **Total Amount Financed** - ₱2,265,500 (Base + MF - your actual loan)
6. **Monthly Amortization** - ₱18,949.55 (over 20 years)
7. **Total Property Cost** - ₱2,495,500 (TCP + MF)

## Important Notes

1. **No UI computation:** UI should display backend values directly, never compute derived values
2. **MF on TCP:** Miscellaneous fees are always calculated on the TCP, not the base loan
3. **Loanable = Total Financed:** The backend's "loanable_amount" is the total financed amount including MF
4. **Upfront vs Financed:** Down payment is upfront, MF is financed (added to loan)

## Comparison by Lending Institution

| Item | HDMF | RCBC | CBC |
|------|------|------|-----|
| Down Payment % | 0% | 10% | 10% |
| Miscellaneous Fees % | 0% | 8.5% | 8.5% |
| Interest Rate | 6.25% (varies) | 8% | 7% |
| Max Term | 30 years | 20 years | 20 years |
| Max Paying Age | 70 | 65 | 65 |

### Same Property Comparison (TCP = ₱2,300,000)

**HDMF:**
- Down Payment: ₱0
- Base Loan: ₱2,300,000
- MF: ₱0
- Total Financed: ₱2,300,000
- Total Cost: ₱2,300,000

**RCBC:**
- Down Payment: ₱230,000
- Base Loan: ₱2,070,000
- MF: ₱195,500
- Total Financed: ₱2,265,500
- Total Cost: ₱2,495,500

**CBC:**
- Down Payment: ₱230,000
- Base Loan: ₱2,070,000
- MF: ₱195,500
- Total Financed: ₱2,265,500
- Total Cost: ₱2,495,500

## Related Documentation

- [Loan Term and Equity FAQ](./LOAN_TERM_AND_EQUITY_FAQ.md)
- [Interest Rate Calculation](./INTEREST_RATE_CALCULATION.md)
- [Loan Term Calculation](./LOAN_TERM_CALCULATION.md)
