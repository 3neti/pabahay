# Interest Rate Calculation Logic

## Overview
This document describes how interest rates are calculated in the mortgage system. The system uses a hierarchical approach to determine the applicable interest rate for a loan, with the ability to override at multiple levels.

## Interest Rate Hierarchy

The interest rate is determined using the following priority order (highest to lowest):

1. **User Override** - Manual override in the calculator form (`balance_payment_interest` field)
2. **Order Override** - Override specified at the order level
3. **Property/Lending Institution** - Property-specific rate or lending institution default
4. **Market Segment Rates** - Rate based on property market segment and price

## Configuration

### Lending Institution Rates

Interest rates are configured in `config/mortgage.php` and stored in the database via Settings classes:

```php
'lending_institutions' => [
    'hdmf' => [
        'interest_rate' => 0.0625,  // 6.25% - varies by price for HDMF
        // ...
    ],
    'rcbc' => [
        'interest_rate' => 0.08,  // 8%
        // ...
    ],
    'cbc' => [
        'interest_rate' => 0.07,  // 7%
        // ...
    ],
]
```

### Settings Classes

Each lending institution has a corresponding Settings class:
- `App\Settings\HdmfSettings`
- `App\Settings\RcbcSettings`
- `App\Settings\CbcSettings`

These are loaded dynamically via the `LendingInstitution::loadSettings()` method.

## HDMF (Pag-IBIG) Special Logic

HDMF uses market segment-based rates that vary by Total Contract Price (TCP):

### Market Segment Rates

| Market Segment         | Price Range      | Interest Rate |
|------------------------|------------------|---------------|
| Socialized/Economic    | ≤ ₱750,000       | 3.0%          |
| Socialized/Economic    | ≤ ₱850,000       | 6.25%         |
| Socialized/Economic    | > ₱850,000       | 6.25%         |
| Open Market            | Any              | 7.0%          |

**Note**: The market segment rate is used as a fallback when no specific rate is set for HDMF.

## Bank Interest Rates

### RCBC (Rizal Commercial Banking Corporation)
- **Default Rate**: 8.0% (0.08)
- **Fixed across all price ranges**

### CBC (China Banking Corporation)
- **Default Rate**: 7.0% (0.07)
- **Fixed across all price ranges**

## Implementation Details

### Calculator Auto-Calculation (Frontend)

In `resources/js/pages/Mortgage/Calculator.vue`, the interest rate is automatically calculated when:
- The Total Contract Price (TCP) changes
- The Lending Institution selection changes

```javascript
const calculateInterestRate = (institution, tcp) => {
    const getMarketRate = (price) => {
        if (price <= 750000) return 0.03;   // 3%
        if (price <= 850000) return 0.0625; // 6.25%
        return 0.0625;                      // 6.25% for > 850k
    };

    const institutionRates = {
        'hdmf': getMarketRate(tcp),  // HDMF uses market segment rates
        'rcbc': 0.08,                // 8%
        'cbc': 0.07,                 // 7%
    };

    return institutionRates[institution] || 0.0625;
};
```

### Manual Override Behavior

When a user manually edits the interest rate field:
1. The `interestRateManuallyEdited` flag is set to `true`
2. Auto-calculation is disabled for subsequent TCP/institution changes
3. The user's manual value is preserved and sent to the backend

### Backend Extraction

The `InterestRateExtractor` class extracts the final interest rate using the hierarchy:

```php
namespace LBHurtado\Mortgage\Extractors;

class InterestRateExtractor extends BaseExtractor
{
    public function extract(): Percent
    {
        // Priority 1: Buyer override
        if ($this->inputs->buyer()->hasInterestRateOverride()) {
            return $this->inputs->buyer()->getInterestRate();
        }

        // Priority 2: Order override
        if ($this->inputs->order()?->hasInterestRateOverride()) {
            return $this->inputs->order()->getInterestRate();
        }

        // Priority 3: Property/Lending Institution
        if ($this->inputs->property()?->hasInterestRateOverride()) {
            return $this->inputs->property()->getInterestRate();
        }

        // Priority 4: Lending institution default
        return $this->inputs->buyer()
            ->getLendingInstitution()
            ->getInterestRate();
    }
}
```

## Data Flow

### User Input → Backend
```
1. User selects lending institution: "hdmf"
2. User enters TCP: ₱850,000
3. Frontend calculates: 6.25% (auto-filled)
4. User can override by typing a different rate
5. Form submits: { lending_institution: "hdmf", total_contract_price: 850000, balance_payment_interest: 0.0625 }
```

### Backend Processing
```
1. MortgageInputsData created from request
2. MortgageParticularsFactory creates domain objects
3. InterestRateExtractor checks hierarchy:
   - User override? (balance_payment_interest) → Use it
   - Order override? → Use it
   - Property override? → Use it
   - Lending institution default → Use it
4. Final rate used in amortization calculations
```

## Examples

### Example 1: HDMF - Low Price (Auto-calculated)
- TCP: ₱750,000
- Lending Institution: HDMF
- **Calculated Rate**: 3.0%
- User does not override
- **Final Rate**: 3.0%

### Example 2: HDMF - Mid Price (Manual Override)
- TCP: ₱850,000
- Lending Institution: HDMF
- **Calculated Rate**: 6.25%
- User manually overrides to: 5.0%
- **Final Rate**: 5.0% (user override takes precedence)

### Example 3: RCBC - Any Price
- TCP: ₱1,200,000
- Lending Institution: RCBC
- **Calculated Rate**: 8.0%
- User does not override
- **Final Rate**: 8.0%

### Example 4: CBC - Any Price
- TCP: ₱900,000
- Lending Institution: CBC
- **Calculated Rate**: 7.0%
- User does not override
- **Final Rate**: 7.0%

## Testing

Interest rate calculations are tested in:
- `tests/Unit/Package/MortgageComputationTest.php` - Contains 29 test cases with various interest rate scenarios
- Each test case verifies the correct interest rate is applied based on lending institution and overrides

## UI Components

### Interest Rate Input Field
Location: `resources/js/pages/Mortgage/Calculator.vue` (lines 389-405)

Features:
- Auto-calculates based on TCP and lending institution
- Shows both decimal (0.0625) and percentage (6.25%)
- Accepts manual input
- Displays helper text: "(auto-calculated, editable)"
- Step increment: 0.0001 for precision

### Display Format
- Input field shows decimal: `0.0625`
- Helper text shows percentage: `6.25%`
- Results section shows percentage: `6.25%`

## Related Files

### Configuration
- `config/mortgage.php` - Lending institution configs
- `database/migrations/2025_11_22_040148_seed_lending_institution_settings.php` - Seeds settings

### Backend
- `packages/lbhurtado/mortgage/src/Extractors/InterestRateExtractor.php` - Extraction logic
- `packages/lbhurtado/mortgage/src/Classes/LendingInstitution.php` - Institution model
- `app/Settings/HdmfSettings.php` - HDMF settings
- `app/Settings/RcbcSettings.php` - RCBC settings
- `app/Settings/CbcSettings.php` - CBC settings

### Frontend
- `resources/js/pages/Mortgage/Calculator.vue` - Calculator with auto-calculation
- Lines 19-36: `calculateInterestRate()` function
- Lines 57-66: Vue watcher for auto-update
- Lines 389-405: Interest rate input field

## Maintenance Notes

### Updating Interest Rates

To update interest rates:

1. **Via Configuration** (affects new installations):
   ```php
   // config/mortgage.php
   'rcbc' => [
       'interest_rate' => 0.085,  // Change from 8% to 8.5%
   ]
   ```

2. **Via Database** (affects running system):
   Update the `settings` table or use the Filament admin interface (when implemented).

3. **Frontend Calculator** (for auto-calculation):
   ```javascript
   // resources/js/pages/Mortgage/Calculator.vue
   const institutionRates = {
       'rcbc': 0.085,  // Update here for calculator auto-fill
   };
   ```

### Adding New Lending Institutions

1. Add configuration in `config/mortgage.php`
2. Create Settings class: `App\Settings\NewInstitutionSettings`
3. Add migration to seed settings
4. Update `LendingInstitution::loadSettings()` match statement
5. Update frontend `calculateInterestRate()` function
6. Add tests for new institution

## Common Issues

### Issue: Auto-calculation not updating
**Cause**: Manual edit flag not being reset
**Solution**: Reset form or refresh page

### Issue: Different rates in frontend vs backend
**Cause**: Frontend calculation out of sync with backend config
**Solution**: Ensure frontend `calculateInterestRate()` matches backend rates

### Issue: HDMF rate not varying by price
**Cause**: Using fixed rate instead of market segment logic
**Solution**: Verify `calculateInterestRate()` uses `getMarketRate(tcp)` for HDMF
