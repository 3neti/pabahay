# Product Selection Service

## Overview
Automatically select the best product for a buyer based on their financial profile, business rules, and ranking strategies. Similar to Google's ad auction system, products are scored and ranked using multiple weighted factors.

## Core Concept: Product Score

Each product receives a **Product Score** that determines its ranking:

```
Product Score = (Affordability × W1) + (Business Priority × W2) + 
                (Customer Benefit × W3) + (Strategic Value × W4)
```

Where W1, W2, W3, W4 are configurable weights that sum to 1.0

## Ranking Strategies

### 1. **Affordability-First** (Default)
**Best for:** Maximizing buyer qualification rate
**Weights:** Affordability (60%), Business Priority (20%), Customer Benefit (20%)

**Scoring:**
- Products buyer can afford get higher scores
- Cheaper monthly payment = higher score
- Lower total cost = bonus points
- Ensures buyer qualifies for the loan

**Example:**
```
Product A: ₱2,300,000, ₱18,949/month → Score: 85
Product B: ₱1,850,000, ₱15,250/month → Score: 95 ← Selected
```

### 2. **Business-First** (Revenue Optimization)
**Best for:** Maximizing company revenue/commissions
**Weights:** Business Priority (50%), Affordability (30%), Customer Benefit (20%)

**Scoring:**
- Partner commissions/incentives heavily weighted
- Premium products with higher margins score higher
- Preferred lending institutions boosted
- Buyer must still qualify (minimum affordability threshold)

**Example:**
```
Product A (RCBC, High Commission): Score: 90 ← Selected
Product B (HDMF, Low Commission): Score: 75
```

### 3. **Customer-First** (Best Value)
**Best for:** Building long-term customer relationships
**Weights:** Customer Benefit (50%), Affordability (40%), Business Priority (10%)

**Scoring:**
- Lowest total interest paid = highest score
- Shortest loan term available = bonus
- Lowest fees = bonus
- Best interest rate = bonus

**Example:**
```
Product A (HDMF, 6.25%, 30 years): Score: 95 ← Selected
Product B (RCBC, 8%, 20 years): Score: 75
```

### 4. **Balanced** (Hybrid Approach)
**Best for:** General use
**Weights:** All factors equally weighted (25% each)

**Scoring:**
- Balances all considerations
- No single factor dominates
- Most "fair" approach

### 5. **Strategic** (Campaign-Driven)
**Best for:** Running promotions or clearing inventory
**Weights:** Dynamic based on campaign goals

**Scoring:**
- Featured products get massive boost (2x-5x multiplier)
- Time-limited promotions
- Inventory clearing (phase-out products)
- Seasonal campaigns

**Example:**
```
Product A (Regular): Score: 80
Product B (Featured Campaign): Score: 80 × 3 = 240 ← Selected
```

### 6. **AI-Optimized** (Machine Learning)
**Best for:** Long-term optimization
**Weights:** Learned from historical conversion data

**Scoring:**
- Analyze which products lead to successful sales
- Consider buyer demographics and behavior
- Continuously optimize weights
- A/B testing built-in

## Scoring Components

### A. Affordability Score (0-100)
```
Affordability Score = 100 - (Monthly Payment / Disposable Income × 100)

If Monthly Payment > Disposable Income: Score = 0
If Monthly Payment < 50% Disposable Income: Score = 100
```

**Factors:**
- Debt-to-income ratio
- Required equity gap
- Qualification status
- Payment-to-income ratio

### B. Business Priority Score (0-100)
```
Business Priority = Base Priority + Commission Weight + Partner Incentive
```

**Factors:**
- Partner commission rate (0-50 points)
- Strategic partnership bonus (0-25 points)
- Inventory velocity (0-15 points)
- Margin level (0-10 points)

**Configuration:**
```php
'business_priority' => [
    'rcbc' => [
        'base_priority' => 80,
        'commission_rate' => 0.025,  // 2.5% commission
        'partnership_bonus' => 15,
        'inventory_boost' => 10,
    ],
    'hdmf' => [
        'base_priority' => 50,
        'commission_rate' => 0,
        'partnership_bonus' => 0,
        'inventory_boost' => 0,
    ],
    'cbc' => [
        'base_priority' => 70,
        'commission_rate' => 0.02,
        'partnership_bonus' => 10,
        'inventory_boost' => 5,
    ],
],
```

### C. Customer Benefit Score (0-100)
```
Customer Benefit = 100 - (Total Interest / TCP × 100)
```

**Factors:**
- Total interest paid over life of loan (lower = better)
- Total fees (lower = better)
- Interest rate (lower = better)
- Loan flexibility (prepayment options, etc.)

**Example:**
```
HDMF: Total Interest = ₱800,000 → Score: 65
RCBC: Total Interest = ₱2,282,000 → Score: 1
```

### D. Strategic Value Score (0-100)
**Dynamic based on business goals**

**Factors:**
- Featured product boost (+50 points)
- New partnership launch (+30 points)
- Inventory clearing (+40 points)
- Seasonal campaign (+25 points)
- Geographic targeting (+20 points)

## Rule Engine

### Priority Rules (Override Scoring)

**1. Hard Constraints** (Disqualify products)
- Buyer doesn't qualify (income gap)
- Product unavailable
- Buyer age exceeds limits
- Outside geographic coverage

**2. Business Rules** (Boost/Penalize)
- Partner quotas (must push RCBC this month)
- Inventory targets (clear old units)
- Regional preferences (promote local banks)
- Customer segment rules (OFW gets priority HDMF)

**3. Customer Rules** (Personalization)
- Repeat buyer preferences
- Previous lending institution
- Referral source preferences
- Geographic location preferences

### Rule Configuration Example

```php
'selection_rules' => [
    [
        'name' => 'RCBC Monthly Quota',
        'active' => true,
        'priority' => 100,
        'condition' => 'current_month_rcbc_count < quota',
        'action' => 'boost',
        'boost_multiplier' => 1.5,
        'applies_to' => ['lending_institution' => 'rcbc'],
    ],
    [
        'name' => 'HDMF for Affordable Housing',
        'active' => true,
        'priority' => 80,
        'condition' => 'tcp <= 850000',
        'action' => 'boost',
        'boost_multiplier' => 2.0,
        'applies_to' => ['lending_institution' => 'hdmf'],
    ],
    [
        'name' => 'Premium Buyers get Banks',
        'active' => true,
        'priority' => 70,
        'condition' => 'monthly_gross_income >= 100000',
        'action' => 'boost',
        'boost_multiplier' => 1.3,
        'applies_to' => ['lending_institution' => ['rcbc', 'cbc']],
    ],
],
```

## Implementation Architecture

### 1. Database Schema

**products table:**
```sql
ALTER TABLE products ADD COLUMN base_priority INT DEFAULT 50;
ALTER TABLE products ADD COLUMN commission_rate DECIMAL(5,4) DEFAULT 0;
ALTER TABLE products ADD COLUMN is_featured BOOLEAN DEFAULT false;
ALTER TABLE products ADD COLUMN boost_multiplier DECIMAL(3,2) DEFAULT 1.0;
ALTER TABLE products ADD COLUMN strategic_tags JSON;
```

**product_selection_rules table:**
```sql
CREATE TABLE product_selection_rules (
    id BIGINT PRIMARY KEY,
    name VARCHAR(255),
    active BOOLEAN DEFAULT true,
    priority INT DEFAULT 50,
    condition TEXT,
    action VARCHAR(50),  -- boost, penalize, require, exclude
    multiplier DECIMAL(3,2) DEFAULT 1.0,
    applies_to JSON,
    starts_at TIMESTAMP NULL,
    ends_at TIMESTAMP NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

**product_selection_logs table:** (Analytics)
```sql
CREATE TABLE product_selection_logs (
    id BIGINT PRIMARY KEY,
    session_id VARCHAR(255),
    buyer_profile JSON,
    strategy_used VARCHAR(50),
    products_evaluated JSON,
    selected_product_id BIGINT,
    selection_score DECIMAL(8,2),
    rules_applied JSON,
    created_at TIMESTAMP
);
```

### 2. Service Classes

**ProductSelectorService:**
```php
class ProductSelectorService
{
    public function selectBestProduct(
        BuyerProfile $buyer,
        Collection $availableProducts,
        string $strategy = 'balanced'
    ): ?Product;
    
    public function rankProducts(
        BuyerProfile $buyer,
        Collection $products,
        string $strategy
    ): Collection;
    
    public function calculateScore(
        Product $product,
        BuyerProfile $buyer,
        string $strategy
    ): ProductScore;
}
```

**ProductScore (Value Object):**
```php
class ProductScore
{
    public float $total;
    public float $affordability;
    public float $businessPriority;
    public float $customerBenefit;
    public float $strategicValue;
    public array $appliedRules;
    public string $reasoning;
}
```

**ScoringStrategy Interface:**
```php
interface ScoringStrategy
{
    public function calculateScore(
        Product $product,
        BuyerProfile $buyer,
        MortgageComputationData $computation
    ): ProductScore;
    
    public function getWeights(): array;
}
```

### 3. Strategy Implementations

```php
class AffordabilityFirstStrategy implements ScoringStrategy { }
class BusinessFirstStrategy implements ScoringStrategy { }
class CustomerFirstStrategy implements ScoringStrategy { }
class BalancedStrategy implements ScoringStrategy { }
class StrategicStrategy implements ScoringStrategy { }
class AIOptimizedStrategy implements ScoringStrategy { }
```

## Usage Examples

### Example 1: Default Selection (Balanced)
```php
$selector = app(ProductSelectorService::class);
$buyer = BuyerProfile::fromInputs($age, $income);
$products = Product::active()->get();

$selected = $selector->selectBestProduct($buyer, $products);
// Returns: Product with highest balanced score
```

### Example 2: Business-First with Override
```php
$selected = $selector->selectBestProduct(
    $buyer, 
    $products, 
    strategy: 'business-first'
);
// Returns: RCBC product (highest commission)
```

### Example 3: Get Top 3 for User Choice
```php
$ranked = $selector->rankProducts($buyer, $products, 'customer-first');
$topThree = $ranked->take(3);

// Returns:
// 1. Product B (HDMF) - Score: 95 - "Lowest total cost"
// 2. Product A (RCBC) - Score: 82 - "Competitive rate"
// 3. Product C (CBC)  - Score: 78 - "Good value"
```

## API Integration

### New Endpoint: Auto-Select Product

**POST /api/v1/mortgage/product/select**

**Request:**
```json
{
  "age": 30,
  "monthly_gross_income": 75000,
  "strategy": "balanced",
  "return_top_n": 3
}
```

**Response:**
```json
{
  "selected_product": {
    "id": "product-e",
    "name": "Product E",
    "lending_institution": "rcbc",
    "price": 2300000,
    "score": 87.5,
    "reasoning": "Best balance of affordability and business value"
  },
  "alternatives": [
    {
      "id": "product-b",
      "score": 85.2,
      "reason": "Lower cost but less commission"
    },
    {
      "id": "product-g",
      "score": 82.1,
      "reason": "Similar cost, different institution"
    }
  ],
  "score_breakdown": {
    "affordability": 88,
    "business_priority": 85,
    "customer_benefit": 80,
    "strategic_value": 95
  },
  "applied_rules": [
    "RCBC Monthly Quota Boost (1.5x)",
    "Affordability Threshold Met"
  ]
}
```

## Admin Interface (Filament)

### Product Configuration
- Set base priority per product
- Configure commission rates
- Toggle featured status
- Set boost multipliers
- Add strategic tags

### Strategy Configuration
- Choose default strategy
- Adjust strategy weights
- Create custom strategies
- A/B test strategies

### Rule Management
- Create/edit selection rules
- Set rule priorities
- Schedule campaigns
- Monitor rule effectiveness

### Analytics Dashboard
- Products selected (by strategy)
- Conversion rates per product
- Commission tracking
- Strategy performance comparison
- A/B test results

## Recommended Implementation Order

### Phase 1: Basic Selection (Week 1)
1. Create database migrations
2. Implement AffordabilityFirstStrategy
3. Basic ProductSelectorService
4. API endpoint
5. Frontend integration

### Phase 2: Business Rules (Week 2)
1. Implement BusinessFirstStrategy
2. Rule engine
3. Product configuration UI (Filament)
4. Rule management UI

### Phase 3: Advanced Features (Week 3)
1. All remaining strategies
2. A/B testing framework
3. Analytics dashboard
4. AI optimization preparation

### Phase 4: AI & Optimization (Week 4)
1. Selection logging
2. Conversion tracking
3. ML model training
4. AI-optimized strategy

## Success Metrics

### Business Metrics
- Conversion rate improvement
- Commission revenue increase
- Inventory turnover rate
- Strategic partner satisfaction

### Customer Metrics
- Average savings per buyer
- Qualification rate
- Customer satisfaction (NPS)
- Return buyer rate

### Technical Metrics
- Selection speed (<100ms)
- Rule evaluation efficiency
- A/B test statistical significance
- Model accuracy (AI strategy)

## Next Steps

1. Review and approve strategy approach
2. Choose initial implementation phase
3. Set business priorities for scoring
4. Define initial rules
5. Begin development
