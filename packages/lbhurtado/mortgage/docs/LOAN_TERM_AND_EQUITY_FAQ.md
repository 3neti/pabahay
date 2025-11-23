# Loan Term and Equity FAQ

This document explains common questions about loan term calculation and equity/down payment requirements.

## Loan Term Calculation

### Why does the frontend show 14 years max but backend computation shows 13 years?

This is due to **age precision** in the backend calculation.

**Frontend Calculation (Integer Age):**
- Uses whole years: Age = 50
- For RCBC: `floor((65 + (-1)) - 50)` = `floor(14)` = **14 years**

**Backend Calculation (Fractional Age):**
- Uses precise age calculation based on birthdate: Age = 50.5 years (if you're 50 years and 6 months old)
- For RCBC: `floor((65 + (-1)) - 50.5)` = `floor(13.5)` = **13 years**

**This is expected behavior.** The backend uses the buyer's exact age based on their birthdate, which may include fractional years. The frontend shows an estimate using integer age.

### How does the offset work?

The **offset** adjusts the maximum paying age for certain lending institutions:

- **HDMF:** offset = 0 (no adjustment)
- **RCBC:** offset = -1 (reduces max by 1 year)
- **CBC:** offset = -1 (reduces max by 1 year)

**Formula:**
```
maxTerm = min(floor((maxPayingAge + offset) - currentAge), institutionMaxTerm)
```

**Example (RCBC, Age 50):**
```
maxTerm = min(floor((65 + (-1)) - 50), 20)
        = min(floor(14), 20)
        = 14 years (frontend estimate)
        = 13 years (backend actual, if age is 50.5)
```

### Why do RCBC and CBC have a -1 offset?

This is a **risk mitigation policy** by the lending institution. Banks often:
1. Want borrowers to complete payments before reaching maximum age
2. Account for potential economic uncertainties
3. Ensure borrowers have a buffer period before retirement

## Down Payment vs Required Equity

### What's the difference between "Down Payment" and "Required Equity"?

These are **two different concepts** in the mortgage package:

#### 1. **Down Payment** (`percent_down_payment`)
- **Standard upfront payment** required by the lending institution
- Set by institution policy (e.g., RCBC: 10%, CBC: 10%, HDMF: 0%)
- Always required regardless of income
- Formula: `downPayment = TCP × percent_down_payment`

#### 2. **Required Equity** (`required_equity`)
- **Additional equity** needed when income is insufficient
- Calculated as: `max(0, loanable_amount - affordable_loan)`
- Only needed when there's an income gap
- Helps close the gap between what you can afford and what you need

### Why is Required Equity zero when I have good income?

**Required Equity = 0** means your income is **sufficient** to afford the loan. You still need to pay the down payment, but no additional equity is required.

**Example (RCBC, TCP = ₱2,300,000, GMI = ₱75,000):**

```
TCP:                    ₱2,300,000
Down Payment (10%):     ₱230,000      ← Still required!
Loanable Amount:        ₱2,070,000

Monthly Income:         ₱75,000
Affordable Loan (PV):   ₱2,200,000    ← Higher than loanable

Required Equity:        max(0, ₱2,070,000 - ₱2,200,000) = ₱0
```

You can afford the loan, so **Required Equity = ₱0**. However, you still need the **₱230,000 down payment**.

### When would Required Equity be greater than zero?

When your income is **insufficient** to afford the full loan:

**Example (RCBC, TCP = ₱2,800,000, GMI = ₱50,000):**

```
TCP:                    ₱2,800,000
Down Payment (10%):     ₱280,000
Loanable Amount:        ₱2,520,000

Monthly Income:         ₱50,000
Affordable Loan (PV):   ₱1,500,000    ← Lower than loanable

Required Equity:        max(0, ₱2,520,000 - ₱1,500,000) = ₱1,020,000
```

You need **additional ₱1,020,000 equity** on top of the ₱280,000 down payment. Total upfront: **₱1,300,000**.

### Summary Table

| Lending Institution | Down Payment (TCP × %) | Misc. Fees (TCP × %) | Required Equity | Total Upfront |
|---------------------|------------------------|----------------------|-----------------|---------------|
| HDMF | 0% | 0% | Varies by income | Down Payment + Required Equity |
| RCBC | 10% | 8.5% | Varies by income | Down Payment + Required Equity |
| CBC | 1**Key Point:** 
- **Down Payment** is mandatory and fixed by institution policy
- **Required Equity** is variable and depends on your income vs loan amount
- When your income is sufficient, Required Equity = ₱0, but Down Payment is still required
- **Miscellaneous Fees** are NOT paid upfront - they are added to the loan and amortized

## Miscellaneous Fees (MF)

### What are Miscellaneous Fees?

**Miscellaneous Fees** are processing and administrative charges imposed by the lending institution. These fees cover:
- Documentation fees
- Appraisal fees
- Administrative costs
- Other bank charges

### How are MF calculated and paid?

**Calculation:** MF = TCP × percent_mf

**Payment Method:** MF is **NOT paid upfront**. Instead, it is:
1. Added to the loanable amount
2. Amortized over the entire loan term
3. Included in your monthly payment

**Example (RCBC, TCP = ₱2,300,000, MF = 8.5%):**

```
TCP:                       ₱2,300,000
Down Payment (10%):        ₱230,000      ← Pay upfront
Base Loan Amount:          ₱2,070,000    (TCP - Down Payment)
Miscellaneous Fees (8.5%):  ₱195,500     ← Added to loan

Total Amount Financed:     ₱2,265,500    ← This is your actual loan
```

Your monthly amortization is calculated on **₱2,265,500**, not just ₱2,070,000.

**Important:** In the mortgage system, the term "Loanable Amount" refers to the **Total Amount Financed** (including MF), not the base loan amount.

### MF by Lending Institution

- **HDMF (Pag-IBIG):** 0% - No miscellaneous fees
- **RCBC:** 8.5% of TCP
- **CBC:** 8.5% of TCP

### Why do banks charge MF?

Banks charge MF to cover the costs of:
1. Property appraisal and inspection
2. Legal documentation and title verification
3. Credit investigation and assessment
4. Loan processing and approval
5. Administrative overhead

### Total Cost Breakdown

**For RCBC/CBC buyers:**

```
TCP:                       ₱2,300,000

Upfront Payments:
  - Down Payment (10%):     ₱230,000
  - Required Equity:        ₱0 (if income sufficient)
  Total Upfront:           ₱230,000

Loan Breakdown:
  - Base Loan:              ₱2,070,000  (TCP - Down Payment)
  - Misc Fees (8.5%):        ₱195,500  (calculated on TCP)
  Total Amount Financed:    ₱2,265,500  ← Your actual loan

Total Property Cost:       ₱2,495,500  (TCP + MF)
```

**Important:** The ₱195,500 MF increases your total property cost to ₱2,495,500, even though the TCP is only ₱2,300,000.

## Related Files
- Backend: `packages/lbhurtado/mortgage/src/Classes/LendingInstitution.php` (line 114-120)
- Backend: `packages/lbhurtado/mortgage/src/Calculators/EquityRequirementCalculator.php`
- Backend: `packages/lbhurtado/mortgage/src/Calculators/RequiredPercentDownPaymentCalculator.php`
- Frontend: `resources/js/pages/Mortgage/Calculator.vue` (lines 100-126)
- Config: `config/mortgage.php` (percent_dp for each institution)
