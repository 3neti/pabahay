# Changelog - November 23, 2025

## Summary
This session focused on implementing comprehensive Miscellaneous Fees (MF) handling and clarifying the mortgage computation display in the UI. Key issues addressed:
1. UI was incorrectly double-counting MF
2. Backend's "loanable_amount" terminology was confusing
3. Need for clearer breakdown of all costs

## Changes Made

### 1. Backend API Enhancements

#### Added New Fields to Response
**File:** `packages/lbhurtado/mortgage/src/Services/MortgageComputationService.php`

Added three new computed fields to the API response:
- `down_payment_amount`: Actual down payment in PHP (TCP × percent)
- `base_loan_amount`: TCP minus down payment, before adding MF
- `total_property_cost`: TCP + MF (true total cost)

**Why:** UI was computing these values, violating the principle that UI should only display backend data.

### 2. Frontend MF Auto-calculation

#### Auto-fill MF Based on Lending Institution
**File:** `resources/js/pages/Mortgage/Calculator.vue`

- Added `calculateMFPercent()` function with institution-specific rates:
  - HDMF: 0%
  - RCBC: 8.5%
  - CBC: 8.5%
- Added watcher to auto-fill `percent_miscellaneous_fee` when institution changes
- Tracks manual edits with `mfManuallyEdited` flag (similar to interest rate)
- Resets on product selection change

**Why:** Improve UX by auto-filling correct MF, but allow manual override.

### 3. UI Display Corrections

#### Fixed Computation Results Display
**File:** `resources/js/pages/Mortgage/Calculator.vue` (lines 773-792)

**Before:** 
- Showed only "Loanable Amount" (confusing)
- UI calculated `total_amount_financed = loanable_amount + miscellaneous_fees` (WRONG - double counting!)

**After:**
- Down Payment: ₱230,000 (with % label, "paid upfront")
- Base Loan Amount: ₱2,070,000 ("TCP minus down payment")
- Miscellaneous Fees: ₱195,500 (with % label, "added to loan")
- Total Amount Financed: ₱2,265,500 (highlighted, "your actual loan")

All values come directly from backend - **no UI computation**.

#### Updated Details Section
- Changed "Total Amount Financed" to "Total Property Cost"
- Shows backend's `total_property_cost` (TCP + MF)
- All values from backend response

### 4. UI Helper Text Improvements

#### MF Input Field
**File:** `resources/js/pages/Mortgage/Calculator.vue` (lines 674-690)

Added helper text showing:
- Percentage formatted: "8.5%"
- Context: "• Added to loan and amortized over term"

**Why:** Clarify that MF is NOT paid upfront.

### 5. Documentation

#### Created Three New Documents

**A. `COMPUTATION_BREAKDOWN.md`**
- Complete step-by-step calculation example
- JSON response documentation
- Terminology clarification
- UI display recommendations
- Comparison across lending institutions

**B. Updated `LOAN_TERM_AND_EQUITY_FAQ.md`**
- Added comprehensive "Miscellaneous Fees" section
- Explained what MF covers
- Clarified MF is added to loan, not paid upfront
- Examples and cost breakdowns
- Updated summary table to include MF column

**C. `CHANGELOG_2025-11-23.md`**
- This document summarizing all changes

## Key Concepts Clarified

### Loanable Amount
**Backend Definition:** Total Amount Financed (Base Loan + MF)
- This is the amount the bank lends you
- Includes miscellaneous fees
- Monthly payment calculated on this amount

### Base Loan Amount
- TCP minus Down Payment
- Does NOT include MF
- New field added for clarity

### Miscellaneous Fees
- Always calculated on TCP (not base loan)
- Added to loan and amortized
- NOT paid upfront
- RCBC/CBC: 8.5%, HDMF: 0%

### Total Property Cost
- TCP + Miscellaneous Fees
- The true total ownership cost
- Different from TCP alone

## Example Calculation (RCBC, TCP = ₱2,300,000)

```
TCP:                      ₱2,300,000
Down Payment (10%):       ₱230,000    ← Paid upfront
Base Loan:                ₱2,070,000  ← TCP - Down Payment
Miscellaneous Fees (8.5%): ₱195,500   ← Added to loan
Total Amount Financed:    ₱2,265,500  ← Your actual loan
Total Property Cost:      ₱2,495,500  ← TCP + MF
Monthly Amortization:     ₱18,949.55  ← Over 20 years
```

## Files Modified

### Backend
1. `packages/lbhurtado/mortgage/src/Services/MortgageComputationService.php`
   - Lines 67-97: Added base_loan_amount, down_payment_amount, total_property_cost

### Frontend
1. `resources/js/pages/Mortgage/Calculator.vue`
   - Lines 80-126: Added MF auto-calculation logic
   - Lines 67-77: Updated product change handler
   - Lines 674-690: Improved MF input with helper text
   - Lines 773-792: Fixed computation results display
   - Lines 811-821: Updated details section

### Documentation
1. `packages/lbhurtado/mortgage/docs/COMPUTATION_BREAKDOWN.md` (NEW)
2. `packages/lbhurtado/mortgage/docs/LOAN_TERM_AND_EQUITY_FAQ.md` (UPDATED)
3. `packages/lbhurtado/mortgage/docs/CHANGELOG_2025-11-23.md` (NEW)

## Testing Performed

### Manual Testing via Tinker
```bash
php artisan tinker --execute="..."
```

**Test Scenario:**
- Institution: RCBC
- TCP: ₱2,300,000
- Age: 30
- GMI: ₱75,000

**Verified:**
- down_payment_amount: ₱230,000 ✓
- base_loan_amount: ₱2,070,000 ✓
- miscellaneous_fees: ₱195,500 ✓
- loanable_amount: ₱2,265,500 ✓
- total_property_cost: ₱2,495,500 ✓

### Frontend Build
```bash
npm run build
```
Build successful - all assets compiled.

## Breaking Changes

**None.** All changes are additive:
- New backend fields added to existing response
- Existing fields unchanged
- Backward compatible

## Migration Notes

### For Frontend Developers
- Stop computing any derived values in UI
- Use new backend fields: `base_loan_amount`, `down_payment_amount`, `total_property_cost`
- Display only what backend returns

### For API Consumers
- New fields available in `/api/v1/mortgage/compute` response
- Existing fields remain unchanged
- No action required for existing integrations

## Next Steps

### Recommended Enhancements
1. Add visual breakdown chart showing TCP → Down Payment → Base Loan → MF → Total Financed
2. Add "Cost Comparison" widget comparing HDMF vs RCBC vs CBC for same property
3. Add tooltip explanations for each term (Down Payment, MF, etc.)
4. Consider adding "Print Breakdown" or "Export PDF" feature

### Future Considerations
1. Make MF rates configurable per product (not just institution)
2. Add processing fee breakdown (currently lumped with MF)
3. Support for other fee types (appraisal, legal, etc.)
4. Multi-currency support for OFW buyers

## Related Issues Resolved

1. ✅ MF was being double-counted in UI display
2. ✅ "Loanable Amount" terminology was confusing
3. ✅ No clear breakdown of what buyer pays upfront vs financed
4. ✅ UI was computing values instead of displaying backend data
5. ✅ MF percent had to be manually entered for each institution

## Contributors
- Session Date: November 23, 2025
- Focus: MF handling, UI clarity, backend API enhancement
