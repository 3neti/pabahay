# Loan Term Calculation Logic

## Overview
This document describes how loan terms (balance payment term) are calculated in the mortgage system. The loan term is determined by the borrower's age, the lending institution's policies, and regulatory constraints.

## Key Concepts

### Age Terminology
- **Borrowing Age**: The borrower's current age when applying for the loan
- **Maximum Age to Apply**: The oldest age at which someone can apply for a loan (typically 60 years)
- **Maximum Age to Pay**: The oldest age by which the loan must be fully paid (varies by institution)
- **Age Offset**: An adjustment factor applied to the maximum paying age (can be positive or negative)

### Term Constraints
- **Maximum Loan Term**: The longest loan period allowed by the institution (in years)
- **Calculated Term**: The actual loan term based on borrower's age
- **Final Term**: The minimum of calculated term and maximum term

## Loan Term Formula

```
Final Loan Term = min(floor((Maximum Paying Age + Age Offset) - Current Age), Maximum Term)
```

### Formula Components
1. **Maximum Paying Age** - Institution-specific age limit for loan completion
2. **Age Offset** - Institution-specific adjustment (usually 0 or -1)
3. **Current Age** - Borrower's age at loan application
4. **Maximum Term** - Institution's maximum allowable loan period

## Lending Institution Configurations

### HDMF (Pag-IBIG Fund)

| Parameter | Value |
|-----------|-------|
| Maximum Age to Apply | 60 years |
| Maximum Age to Pay | 70 years |
| Age Offset | 0 |
| Maximum Loan Term | 30 years |

**Calculation Example**:
```
30-year-old borrower:
  Limit = 70 + 0 = 70
  Term = min(floor(70 - 30), 30) = min(40, 30) = 30 years

45-year-old borrower:
  Limit = 70 + 0 = 70
  Term = min(floor(70 - 45), 30) = min(25, 30) = 25 years

55-year-old borrower:
  Limit = 70 + 0 = 70
  Term = min(floor(70 - 55), 30) = min(15, 30) = 15 years
```

### RCBC (Rizal Commercial Banking Corporation)

| Parameter | Value |
|-----------|-------|
| Maximum Age to Apply | 60 years |
| Maximum Age to Pay | 65 years |
| Age Offset | -1 |
| Maximum Loan Term | 20 years |

**Calculation Example**:
```
30-year-old borrower:
  Limit = 65 + (-1) = 64
  Term = min(floor(64 - 30), 20) = min(34, 20) = 20 years

45-year-old borrower:
  Limit = 65 + (-1) = 64
  Term = min(floor(64 - 45), 20) = min(19, 20) = 19 years

50-year-old borrower:
  Limit = 65 + (-1) = 64
  Term = min(floor(64 - 50), 20) = min(14, 20) = 14 years
```

### CBC (China Banking Corporation)

| Parameter | Value |
|-----------|-------|
| Maximum Age to Apply | 60 years |
| Maximum Age to Pay | 65 years |
| Age Offset | -1 |
| Maximum Loan Term | 20 years |

**Note**: CBC has identical parameters to RCBC.

## Co-Borrower Considerations

When there is a co-borrower, the system uses the **oldest borrower's age** for loan term calculation. This is a conservative approach that ensures the loan can be repaid within the oldest borrower's qualifying period.

### Example with Co-Borrower

**Scenario**: HDMF loan with primary borrower (30 years old) and co-borrower (50 years old)

```
Primary Borrower: 30 years old → Would qualify for 30 years (min(70-30, 30) = 30)
Co-Borrower: 50 years old → Would qualify for 20 years (min(70-50, 30) = 20)

Final Term: 20 years (based on oldest borrower)
```

## Implementation Details

### Configuration (`config/mortgage.php`)

```php
'lending_institutions' => [
    'hdmf' => [
        'borrowing_age' => [
            'minimum' => 18,
            'maximum' => 60,
            'offset' => 0,
        ],
        'maximum_term' => 30,
        'maximum_paying_age' => 70,
    ],
    'rcbc' => [
        'borrowing_age' => [
            'minimum' => 18,
            'maximum' => 60,
            'offset' => -1,
        ],
        'maximum_term' => 20,
        'maximum_paying_age' => 65,
    ],
    'cbc' => [
        'borrowing_age' => [
            'minimum' => 18,
            'maximum' => 60,
            'offset' => -1,
        ],
        'maximum_term' => 20,
        'maximum_paying_age' => 65,
    ],
]
```

### Backend Calculation

#### LendingInstitution Class
Location: `packages/lbhurtado/mortgage/src/Classes/LendingInstitution.php`

```php
public function maxAllowedTerm(Carbon $birthdate, ?int $overridePayingAge = null): int
{
    $age = app(AgeService::class)->getAgeInFloat($birthdate);
    $limit = ($overridePayingAge ?? $this->maximumPayingAge()) + $this->offset();
    
    return min((int) floor($limit - $age), $this->maximumTerm());
}
```

**Parameters**:
- `$birthdate` - The borrower's birthdate
- `$overridePayingAge` - Optional override for maximum paying age (rarely used)

**Returns**: Integer representing the loan term in years

#### BalancePaymentTermCalculator
Location: `packages/lbhurtado/mortgage/src/Calculators/BalancePaymentTermCalculator.php`

```php
public function calculate(): int
{
    $oldest = $this->inputs->buyer()->getOldestAmongst();
    $lending_institution = ExtractorFactory::make(ExtractorType::LENDING_INSTITUTION, $this->inputs)->extract();
    
    return $lending_institution->maxAllowedTerm($oldest->getBirthdate(), $oldest->getOverrideMaximumPayingAge());
}
```

**Process**:
1. Find the oldest borrower (primary or co-borrower)
2. Extract the lending institution
3. Calculate term based on oldest borrower's age

#### AgeService
Location: `packages/lbhurtado/mortgage/src/Services/AgeService.php`

```php
public function getAgeInFloat(Carbon $birthdate): float
{
    return round($birthdate->diffInDays(now()) / 365.25, 1);
}
```

**Note**: Uses 365.25 to account for leap years, returns age as float with 1 decimal precision.

### Buyer Class Methods

```php
// Get the oldest borrower among primary and co-borrowers
public function getOldestAmongst(): Buyer
{
    $oldest = $this;
    
    $this->co_borrowers->each(function (Buyer $co_borrower) use (&$oldest) {
        if ($co_borrower->getBirthdate()->lt($oldest->getBirthdate())) {
            $oldest = $co_borrower;
        }
    });
    
    return $oldest;
}

// Get maximum term allowed for this buyer
public function getMaximumTermAllowed(): int
{
    return $this->getLendingInstitution()?->maxAllowedTerm($this->getBirthdate(), $this->getOverrideMaximumPayingAge()) ?? 30;
}

// Get joint maximum term (based on oldest co-borrower)
public function getJointMaximumTermAllowed(): int
{
    return $this->getOldestAmongst()->getMaximumTermAllowed();
}
```

## Data Flow

### Calculator Flow
```
1. User inputs age: 30
2. User selects lending institution: HDMF
3. Frontend sends to backend
4. Backend creates Buyer object with age 30
5. Backend creates LendingInstitution object (HDMF)
6. BalancePaymentTermCalculator calls:
   - buyer.getOldestAmongst() → returns primary buyer (age 30)
   - lending_institution.maxAllowedTerm(birthdate) →
     - Gets age: 30.0
     - Calculates limit: 70 + 0 = 70
     - Returns: min(floor(70 - 30), 30) = min(40, 30) = 30
7. Result: 30-year loan term
```

## Comprehensive Examples

### Example 1: Young HDMF Borrower
- **Age**: 25 years
- **Institution**: HDMF
- **Calculation**: min(floor(70 - 25), 30) = min(45, 30) = **30 years**
- **Reason**: Maximum term caps the available period

### Example 2: Middle-aged HDMF Borrower
- **Age**: 47 years
- **Institution**: HDMF
- **Calculation**: min(floor(70 - 47), 30) = min(23, 30) = **23 years**
- **Reason**: Age constraint is limiting factor

### Example 3: Young RCBC Borrower
- **Age**: 30 years
- **Institution**: RCBC
- **Calculation**: min(floor(64 - 30), 20) = min(34, 20) = **20 years**
- **Reason**: Maximum term caps the available period (note: offset reduces effective age)

### Example 4: Middle-aged RCBC Borrower
- **Age**: 49 years
- **Institution**: RCBC
- **Calculation**: min(floor(64 - 49), 20) = min(15, 20) = **15 years**
- **Reason**: Age constraint is limiting factor

### Example 5: HDMF with Co-Borrower
- **Primary**: 30 years
- **Co-Borrower**: 50 years
- **Institution**: HDMF
- **Oldest**: 50 years (co-borrower)
- **Calculation**: min(floor(70 - 50), 30) = min(20, 30) = **20 years**
- **Reason**: Uses oldest borrower's age

### Example 6: RCBC with Co-Borrower
- **Primary**: 45 years
- **Co-Borrower**: 50 years
- **Institution**: RCBC
- **Oldest**: 50 years (co-borrower)
- **Calculation**: min(floor(64 - 50), 20) = min(14, 20) = **14 years**
- **Reason**: Uses oldest borrower's age with offset

## Testing

### Unit Tests
Location: `tests/Unit/Package/MortgageComputationTest.php`

The test suite includes 29 test cases covering various scenarios:

**Sample Test Cases**:
```php
// HDMF scenarios
'49yo → 21 years' => ['hdmf', 1_000_000, 49, ..., 21, ...]
'47yo → 23 years' => ['hdmf', 1_000_000, 47, ..., 23, ...]
'48yo → 22 years' => ['hdmf', 1_100_000, 48, ..., 22, ...]

// RCBC scenarios  
'49yo → 15 years' => ['rcbc', 1_000_000, 49, ..., 15, ...]
'48yo → 16 years' => ['rcbc', 1_100_000, 48, ..., 16, ...]
'47yo → 17 years' => ['rcbc', 1_200_000, 47, ..., 17, ...]

// Co-borrower scenarios
'48yo + 50yo co-borrower → 20 years' => ['hdmf', 1_000_000, 48, 50, ..., 20, ...]
'45yo + 50yo co-borrower → 14 years' => ['rcbc', 1_400_000, 45, 50, ..., 14, ...]
```

**Assertion**:
```php
expect($actual_balance_payment_term)->toBe($expected_balance_payment_term)
```

All 29 tests pass, validating the loan term calculation logic across different ages, institutions, and co-borrower scenarios.

## Related Files

### Configuration
- `config/mortgage.php` - Lines 23-82 (lending institution configs)

### Backend Core
- `packages/lbhurtado/mortgage/src/Classes/LendingInstitution.php`
  - Lines 104-120: `maximumTerm()`, `maximumPayingAge()`, `maxAllowedTerm()`
- `packages/lbhurtado/mortgage/src/Classes/Buyer.php`
  - Lines 45-53: Age limit getters
  - Lines 117-132: Age setters/getters
  - Lines 134-145: `getOldestAmongst()`
  - Lines 173-182: `getMaximumTermAllowed()`, `getJointMaximumTermAllowed()`
- `packages/lbhurtado/mortgage/src/Services/AgeService.php`
  - Lines 14-17: `getAgeInFloat()`
- `packages/lbhurtado/mortgage/src/Calculators/BalancePaymentTermCalculator.php`
  - Lines 13-19: Main calculation method

### Settings
- `app/Settings/HdmfSettings.php` - Lines 18-19: `maximum_term`, `maximum_paying_age`
- `app/Settings/RcbcSettings.php` - Lines 18-19: `maximum_term`, `maximum_paying_age`
- `app/Settings/CbcSettings.php` - Lines 18-19: `maximum_term`, `maximum_paying_age`

### Database
- `database/migrations/2025_11_22_040148_seed_lending_institution_settings.php`
  - Lines 33-34, 51-52, 69-70: Seeding term and age configurations

### Tests
- `tests/Unit/Package/MortgageComputationTest.php` - Lines 17-65, 67-200: Test dataset and assertions

## Age Calculation Precision

The system uses **floating-point age** calculation with 1 decimal place precision:

```php
$age = round($birthdate->diffInDays(now()) / 365.25, 1);
```

**Example**:
- Birthdate: 1994-06-15
- Current Date: 2024-11-22
- Days difference: 11,118 days
- Age calculation: 11,118 / 365.25 = 30.4 years
- Rounded: 30.4 years
- Floor for term: floor(70 - 30.4) = floor(39.6) = 39 years

This precision ensures accurate calculations for borrowers whose age is between whole numbers.

## Edge Cases

### Case 1: Borrower at Maximum Age Limit
- **Age**: 60 years (maximum age to apply)
- **Institution**: HDMF
- **Calculation**: min(floor(70 - 60), 30) = min(10, 30) = **10 years**
- **Note**: Can still qualify for 10 years

### Case 2: Borrower Near Maximum Paying Age
- **Age**: 69 years
- **Institution**: HDMF
- **Calculation**: min(floor(70 - 69), 30) = min(1, 30) = **1 year**
- **Note**: Very short term, likely rejected by other qualification criteria

### Case 3: Young Co-borrower with Older Primary
- **Primary**: 55 years
- **Co-Borrower**: 25 years
- **Institution**: HDMF
- **Calculation**: Uses oldest (55 years) = min(floor(70 - 55), 30) = **15 years**
- **Note**: Co-borrower's youth doesn't extend the term

### Case 4: Override Maximum Paying Age (Special Cases)
```php
$term = $lending_institution->maxAllowedTerm($birthdate, overridePayingAge: 75);
```
- Allows custom maximum paying age
- Rarely used, for special programs or exceptions
- Overrides institution's default maximum paying age

## Validation

### Age Validation Rule
Location: `packages/lbhurtado/mortgage/src/Rules/ValidBorrowerAge.php`

Ensures:
- Borrower is at least 18 years old (minimum age)
- Borrower is not older than 60 years (maximum age to apply)
- Age is within valid range for the lending institution

### Loan Term Validation
Location: `packages/lbhurtado/mortgage/src/Rules/ValidLoanTerm.php`

Ensures:
- Calculated term is positive
- Term does not exceed institution's maximum
- Borrower's age + term ≤ maximum paying age

## Maintenance Notes

### Updating Age Limits

To change age limits for a lending institution:

1. **Configuration** (affects new installations):
```php
// config/mortgage.php
'hdmf' => [
    'maximum_paying_age' => 75,  // Increase from 70 to 75
    'maximum_term' => 35,        // Increase from 30 to 35
]
```

2. **Database** (affects running system):
Update the `settings` table or use Filament admin (when implemented)

3. **Re-seed** (development):
```bash
php artisan migrate:fresh --seed
```

### Adding New Lending Institution

1. Add configuration to `config/mortgage.php`
2. Create Settings class extending `Spatie\LaravelSettings\Settings`
3. Add migration to seed initial settings
4. Update `LendingInstitution::loadSettings()` match statement
5. Add test cases for new institution

## Impact on Monthly Amortization

The loan term directly affects the monthly amortization:
- **Longer term** = Lower monthly payment, Higher total interest
- **Shorter term** = Higher monthly payment, Lower total interest

**Example** (₱1,000,000 loan at 6.25%):
- **30 years**: ₱6,158/month, Total interest: ₱1,216,880
- **20 years**: ₱7,199/month, Total interest: ₱727,760
- **15 years**: ₱8,574/month, Total interest: ₱543,320

The system automatically calculates the optimal term based on age constraints and uses it for all subsequent computations.

## Common Questions

### Q: Why does RCBC have an age offset of -1?
**A**: The offset reduces the effective maximum paying age by 1 year, providing a conservative buffer. This is a bank-specific policy decision.

### Q: Can a 61-year-old apply for a loan?
**A**: No, the maximum age to apply is 60 years for all institutions. However, a borrower can apply at 59 and complete payments up to age 70 (HDMF) or 65 (banks).

### Q: What if co-borrowers have very different ages?
**A**: The system always uses the oldest borrower's age, ensuring the loan is feasible for the weakest qualification scenario.

### Q: Can the loan term be manually overridden?
**A**: Not directly in the current implementation. The term is always calculated based on age. Property-specific overrides via `Buyer::setOverrideMaximumPayingAge()` are possible but not exposed in the UI.

### Q: How does this affect qualification?
**A**: A shorter term increases monthly amortization, which may cause qualification failure if disposable income is insufficient. The system calculates qualification after determining the term.
