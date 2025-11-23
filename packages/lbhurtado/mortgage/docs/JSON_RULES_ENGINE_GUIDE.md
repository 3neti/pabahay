# JSON Rules Engine - Usage Guide

## Overview
The JSON Rules Engine allows you to define product selection logic using JSON files - **no PHP coding required**!

## Rules File Location
`storage/app/product_selection_rules.json`

## Rule Structure

```json
{
  "name": "Rule description",
  "priority": 100,
  "active": true,
  "condition": {
    "field_name": {"operator": ">=", "value": 75000}
  },
  "action": {
    "sort_by": "price",
    "direction": "desc"
  }
}
```

## Available Fields

### Buyer Profile Fields
- `age` - Buyer's age (integer)
- `monthly_gross_income` - Monthly income (float)

## Supported Operators

| Operator | Description | Example |
|----------|-------------|---------|
| `=` or `==` | Equals | `{"operator": "=", "value": 30}` |
| `!=` | Not equals | `{"operator": "!=", "value": 30}` |
| `>` | Greater than | `{"operator": ">", "value": 50000}` |
| `<` | Less than | `{"operator": "<", "value": 40}` |
| `>=` | Greater or equal | `{"operator": ">=", "value": 75000}` |
| `<=` | Less or equal | `{"operator": "<=", "value": 35}` |
| `between` | Range (inclusive) | `{"operator": "between", "value": [25, 45]}` |
| `not_between` | Outside range | `{"operator": "not_between", "value": [60, 70]}` |
| `in` | In list | `{"operator": "in", "value": [30, 35, 40]}` |
| `not_in` | Not in list | `{"operator": "not_in", "value": [20, 21, 22]}` |

### Shorthand Syntax
You can also use shorthand:
```json
{">=": 75000}
```
Instead of:
```json
{"operator": ">=", "value": 75000}
```

## Available Actions

### 1. Sort By Field
```json
"action": {
  "sort_by": "price",
  "direction": "desc"
}
```

**Sortable fields:**
- `price` - Product TCP
- `monthly_payment` - Monthly amortization
- `affordability_score` - How affordable (0-100)
- `loan_term` - Loan term in years

**Directions:**
- `asc` - Ascending (lowest first)
- `desc` - Descending (highest first)

### 2. Prefer Institution
```json
"action": {
  "prefer_institution": "hdmf"
}
```
Filters to only show products from specified institution.

**Institutions:** `hdmf`, `rcbc`, `cbc`

### 3. Boost Score
```json
"action": {
  "boost_multiplier": 1.5
}
```
Multiplies affordability scores by factor.

### 4. Combined Actions
```json
"action": {
  "prefer_institution": "rcbc",
  "sort_by": "price",
  "direction": "desc"
}
```

## Example Rules

### Example 1: Income-Based Selection
```json
{
  "name": "High earners get expensive products",
  "priority": 100,
  "active": true,
  "condition": {
    "monthly_gross_income": {">=": 100000}
  },
  "action": {
    "sort_by": "price",
    "direction": "desc"
  }
}
```

**Result:** Buyers earning ₱100k+ get most expensive qualified product.

### Example 2: Age-Based Institution Preference
```json
{
  "name": "Young buyers prefer HDMF",
  "priority": 90,
  "active": true,
  "condition": {
    "age": {"<=": 35}
  },
  "action": {
    "prefer_institution": "hdmf",
    "sort_by": "monthly_payment",
    "direction": "asc"
  }
}
```

**Result:** Buyers ≤35 years get cheapest HDMF product.

### Example 3: Complex Conditions
```json
{
  "name": "Premium mid-career segment",
  "priority": 85,
  "active": true,
  "condition": {
    "age": {"operator": "between", "value": [36, 50]},
    "monthly_gross_income": {">=": 80000}
  },
  "action": {
    "prefer_institution": "rcbc",
    "sort_by": "price",
    "direction": "desc"
  }
}
```

**Result:** Buyers aged 36-50 earning ₱80k+ get most expensive RCBC product.

### Example 4: Default Fallback
```json
{
  "name": "Default: cheapest for everyone else",
  "priority": 1,
  "active": true,
  "condition": {},
  "action": {
    "sort_by": "monthly_payment",
    "direction": "asc"
  }
}
```

**Result:** Empty condition = always matches (fallback rule).

## Rule Priority

Rules are evaluated in **priority order** (highest first). First matching rule wins.

### Priority Recommendations:
- **100-200:** Special/Premium segments
- **50-99:** Standard segments
- **1-49:** Default/Fallback rules

## Multiple Conditions

All conditions must match (implicit AND):

```json
"condition": {
  "age": {">=": 30},
  "monthly_gross_income": {">=": 75000}
}
```

**Result:** Both age ≥ 30 AND income ≥ ₱75,000 must be true.

## Activating/Deactivating Rules

Set `"active": false` to temporarily disable a rule without deleting it:

```json
{
  "name": "Temporarily disabled rule",
  "priority": 80,
  "active": false,
  ...
}
```

## How to Edit Rules

### Method 1: Direct File Edit (Simple)
1. Open `storage/app/product_selection_rules.json`
2. Edit the JSON
3. Save file
4. Clear cache: `php artisan cache:clear`

### Method 2: Via .env (Switch Engines)
Switch to simple mode without rules:
```bash
# .env
PRODUCT_SELECTION_ENGINE=simple
PRODUCT_SELECTION_PREFERENCE=most_expensive
```

Then: `php artisan config:cache`

## Testing Your Rules

### Test Scenario 1: Young Buyer
- Age: 25
- Income: ₱50,000
- **Expected:** HDMF product (Rule Priority 90)

### Test Scenario 2: Premium Buyer  
- Age: 40
- Income: ₱150,000
- **Expected:** Most expensive product (Rule Priority 100)

### Test Scenario 3: Mid-Career
- Age: 42
- Income: ₱85,000
- **Expected:** RCBC expensive product (Rule Priority 85)

## Troubleshooting

### Rules Not Working?
1. Check JSON syntax is valid (use JSON validator)
2. Clear cache: `php artisan cache:clear`
3. Check logs: `storage/logs/laravel.log`
4. Verify file exists: `ls -la storage/app/product_selection_rules.json`

### Rule Not Matching?
1. Check `active` is `true`
2. Verify condition operators are correct
3. Check priority order (higher priority rules may match first)
4. Test with simple condition first: `"condition": {}`

### Disable Rules Engine Temporarily
```bash
# .env
PRODUCT_SELECTION_ENGINE=simple
```

## Performance

- Rules are **cached** by default (1 hour)
- Cache auto-clears on config changes
- Disable cache for testing: `CACHE_PRODUCT_RULES=false`

## Best Practices

1. **Always have a default rule** (priority 1, empty condition)
2. **Use descriptive names** for rules
3. **Test with cache disabled** during development
4. **Keep rules simple** - one clear purpose per rule
5. **Document your logic** in rule names
6. **Order by priority** - highest to lowest

## Example: Complete Rules File

```json
[
  {
    "name": "Ultra premium: 200k+ income gets max price",
    "priority": 110,
    "active": true,
    "condition": {
      "monthly_gross_income": {">=": 200000}
    },
    "action": {
      "sort_by": "price",
      "direction": "desc"
    }
  },
  {
    "name": "Premium: 100k+ income gets expensive",
    "priority": 100,
    "active": true,
    "condition": {
      "monthly_gross_income": {">=": 100000}
    },
    "action": {
      "sort_by": "price",
      "direction": "desc"
    }
  },
  {
    "name": "Young buyers get HDMF long-term",
    "priority": 90,
    "active": true,
    "condition": {
      "age": {"<=": 35}
    },
    "action": {
      "prefer_institution": "hdmf",
      "sort_by": "monthly_payment",
      "direction": "asc"
    }
  },
  {
    "name": "Mid-career gets bank products",
    "priority": 80,
    "active": true,
    "condition": {
      "age": {"between": [36, 50]},
      "monthly_gross_income": {">=": 60000}
    },
    "action": {
      "prefer_institution": "rcbc",
      "sort_by": "price",
      "direction": "desc"
    }
  },
  {
    "name": "Default: cheapest qualified",
    "priority": 1,
    "active": true,
    "condition": {},
    "action": {
      "sort_by": "monthly_payment",
      "direction": "asc"
    }
  }
]
```

## Support

For issues or questions, check:
- Application logs: `storage/logs/laravel.log`
- Config values: `php artisan config:show mortgage.product_selection`
- Clear all caches: `php artisan optimize:clear`
