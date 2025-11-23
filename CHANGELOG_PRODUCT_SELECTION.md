# Changelog - Product Selection System

## [1.0.0] - 2025-11-23

### Added - Phase 1: MVP Product Selection
- **ProductSelectorService**: Core service for ranking products by affordability
  - Computes mortgage for each product with buyer's age and income
  - Calculates affordability score (0-100 based on disposable income ratio)
  - Filters qualified products vs unaffordable products
  - Generates human-readable reasoning for each selection
- **ProductSelectionController**: REST API endpoint at `POST /api/v1/mortgage/product/select`
  - Accepts age and monthly gross income
  - Returns selected product with reasoning and alternatives
- **SelectProductRequest**: Request validation for product selection API
- **Database Migration**: Added product metadata columns
  - `base_priority` - Base ranking priority (1-100)
  - `commission_rate` - Commission percentage for selection bias
  - `is_featured` - Featured product flag
  - `boost_multiplier` - Score multiplier for promotions
- **Frontend Integration** (Calculator.vue):
  - "💡 Get Product Recommendation" button
  - Shows recommended product with reasoning in green alert box
  - Manual trigger (no auto-selection on input change)
  - Loading state with spinner
  - Recommendation can be dismissed

### Added - Phase 2: JSON Rules Engine
- **RuleEngineService**: Flexible JSON-based product selection rules
  - Loads rules from `storage/app/product_selection_rules.json`
  - Evaluates conditions against buyer profile (age, income)
  - Applies actions (sort, filter, prefer institution, boost)
  - Priority-based rule matching (highest priority wins)
  - Rule caching for performance
- **RuleEvaluator**: Condition evaluation engine
  - Supports operators: `=`, `!=`, `>`, `<`, `>=`, `<=`, `between`, `not_between`, `in`, `not_in`
  - Implicit AND logic for multiple conditions
  - Empty condition = always match (default rule)
- **Default Rules** (`product_selection_rules.json`):
  1. **Priority 100**: Premium buyers (income ≥100k) get most expensive product
  2. **Priority 90**: Young buyers (age ≤35) prefer HDMF cheapest product
  3. **Priority 80**: Mid-career (age 36-50, income ≥60k) prefer RCBC expensive product
  4. **Priority 1**: Default fallback - cheapest qualified product
- **Configuration** (`config/mortgage.php`):
  - `product_selection.engine` - Switch between 'rules' or 'simple' mode
  - `product_selection.preference` - Fallback preference ('cheapest' or 'most_expensive')
  - `product_selection.rules_file` - Path to JSON rules file
  - `product_selection.cache_rules` - Enable/disable rule caching
- **Comprehensive Documentation**:
  - `JSON_RULES_ENGINE_GUIDE.md` - Complete guide for creating and managing rules
  - Rule syntax examples
  - Non-programmer friendly format

### Fixed
- **SQLite Database Lock Issue**: Changed `CACHE_STORE` from `database` to `file` to prevent concurrent write locks during rate limiting
- **Manual Recommendation Button Bug**: Fixed issue where button stopped working after manual product selection by adding `forceRun` parameter to bypass `autoSelectEnabled` check

### Technical Details
**Files Created:**
- Backend Services:
  - `packages/lbhurtado/mortgage/src/Services/ProductSelectorService.php`
  - `packages/lbhurtado/mortgage/src/Services/RuleEngineService.php`
  - `packages/lbhurtado/mortgage/src/Services/RuleEvaluator.php`
- API Layer:
  - `app/Http/Controllers/Mortgage/ProductSelectionController.php`
  - `app/Http/Requests/Mortgage/SelectProductRequest.php`
- Configuration:
  - `storage/app/product_selection_rules.json`
  - Updated `config/mortgage.php`
- Documentation:
  - `packages/lbhurtado/mortgage/docs/JSON_RULES_ENGINE_GUIDE.md`
- Database:
  - `database/migrations/YYYY_MM_DD_HHMMSS_add_product_selection_fields_to_products_table.php`

**API Response Format:**
```json
{
  "success": true,
  "selected_product": {
    "product_id": 1,
    "product_name": "Product A - HDMF ₱850,000",
    "lending_institution": "hdmf",
    "price": 850000,
    "monthly_payment": 5233.60,
    "qualifies": true,
    "affordability_score": 80.06,
    "reasoning": "Excellent fit - Well within your budget"
  },
  "alternatives": [...],
  "message": "Product selected successfully"
}
```

### Configuration
Set in `.env`:
```bash
PRODUCT_SELECTION_ENGINE=rules     # 'rules' or 'simple'
PRODUCT_SELECTION_PREFERENCE=cheapest  # 'cheapest' or 'most_expensive'
CACHE_PRODUCT_RULES=true           # Enable rule caching
CACHE_STORE=file                   # Changed from 'database' to avoid locks
```

### Usage Example
**Frontend:**
```vue
<button @click="getProductRecommendation">
  💡 Get Product Recommendation
</button>
```

**Backend:**
```php
$selector = app(ProductSelectorService::class);
$selected = $selector->selectBestProduct($age, $income, $products);
```

**JSON Rule Example:**
```json
{
  "name": "Young buyers prefer HDMF for long term",
  "priority": 90,
  "active": true,
  "condition": {
    "age": {"operator": "<=", "value": 35}
  },
  "action": {
    "prefer_institution": "hdmf",
    "sort_by": "monthly_payment",
    "direction": "asc"
  }
}
```

### Testing Scenarios
1. **Age 25, Income ₱50k** → HDMF cheapest (Priority 90 rule)
2. **Age 40, Income ₱150k** → Most expensive product (Priority 100 rule)
3. **Age 45, Income ₱80k** → RCBC expensive (Priority 80 rule)
4. **Age 50, Income ₱30k** → Cheapest qualified (Priority 1 default)

### Future Enhancements
- [ ] Admin UI (Filament) for managing rules without editing JSON
- [ ] Visual rule builder
- [ ] Rule analytics and reporting
- [ ] A/B testing for rule effectiveness
- [ ] Commission-based ranking integration
- [ ] Featured product boosting
- [ ] Multi-institution preferences
- [ ] Seasonal/promotional rule activation
