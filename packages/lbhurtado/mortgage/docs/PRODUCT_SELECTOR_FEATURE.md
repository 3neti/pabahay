# Product Selector Feature

## Overview
The Product Selector feature allows users to quickly populate the mortgage calculator with predefined product templates. Each product has a preset Total Contract Price (TCP) and lending institution, making it easy for users to calculate mortgages for specific property offerings.

## Purpose
Instead of manually entering the TCP and selecting a lending institution, users can:
1. Select a predefined product from a dropdown
2. Have the TCP and lending institution auto-filled
3. Still maintain the ability to edit the TCP after selection

This is useful for:
- **Real estate agents**: Quickly showing mortgage options for specific properties
- **Buyers**: Exploring different product offerings across lending institutions
- **Marketing**: Pre-configured product showcases

## Configuration

### Products Definition
Products are configured in `config/mortgage.php`:

```php
'products' => [
    [
        'id' => 'product-a',
        'name' => 'Product A',
        'lending_institution' => 'hdmf',
        'price' => 850_000,
    ],
    [
        'id' => 'product-b',
        'name' => 'Product B',
        'lending_institution' => 'hdmf',
        'price' => 1_500_000,
    ],
    // ... more products
],
```

### Current Products

| Product ID | Product Name | Lending Institution | Price |
|------------|--------------|---------------------|-------|
| product-a | Product A | HDMF | ₱850,000 |
| product-b | Product B | HDMF | ₱1,500,000 |
| product-c | Product C | HDMF | ₱2,200,000 |
| product-d | Product D | RCBC | ₱1,850,000 |
| product-e | Product E | RCBC | ₱2,300,000 |
| product-f | Product F | RCBC | ₱2,800,000 |
| product-g | Product G | CBC | ₱2,300,000 |
| product-h | Product H | CBC | ₱2,800,000 |
| product-i | Product I | CBC | ₱3,100,000 |

## User Interface

### Product Selector Dropdown
Location: Top of the calculator form, before Lending Institution section

**Features**:
- Grouped by lending institution using `<optgroup>`
- Shows product name and formatted price
- Optional - users can skip and enter manually
- Helper text explaining the feature

**Dropdown Structure**:
```
-- Select a Product --
HDMF (Pag-IBIG)
  ├─ Product A - ₱850,000.00
  ├─ Product B - ₱1,500,000.00
  └─ Product C - ₱2,200,000.00
RCBC
  ├─ Product D - ₱1,850,000.00
  ├─ Product E - ₱2,300,000.00
  └─ Product F - ₱2,800,000.00
CBC
  ├─ Product G - ₱2,300,000.00
  ├─ Product H - ₱2,800,000.00
  └─ Product I - ₱3,100,000.00
```

### Total Contract Price Field
- Updated with "(editable)" label
- Remains fully editable after product selection
- User can adjust price up or down

## User Flow

### Scenario 1: Using Product Selector
1. User visits `/mortgage-calculator`
2. User sees "Product Selection (Optional)" section at top
3. User selects "Product E - ₱2,300,000.00" from dropdown
4. **Auto-filled**:
   - Total Contract Price: ₱2,300,000
   - Lending Institution: RCBC
   - Interest Rate: 8% (RCBC default)
5. User enters age, income, etc.
6. User clicks "Compute Mortgage"

### Scenario 2: Manual Entry (Existing Flow)
1. User visits `/mortgage-calculator`
2. User ignores product selector (or sees "-- Select a Product --")
3. User manually selects lending institution: HDMF
4. User manually enters TCP: ₱750,000
5. User enters age, income, etc.
6. User clicks "Compute Mortgage"

### Scenario 3: Product + Manual Adjustment
1. User selects "Product A - ₱850,000.00" (HDMF)
2. TCP auto-fills: ₱850,000
3. User edits TCP to: ₱900,000 (user wants a higher price)
4. Lending institution remains: HDMF
5. Interest rate recalculates based on new TCP
6. User proceeds with computation

## Implementation Details

### Backend (Route)
Location: `routes/web.php`

```php
Route::get('/mortgage-calculator', function () {
    return Inertia::render('Mortgage/Calculator', [
        'defaults' => config('mortgage.defaults.calculator'),
        'products' => config('mortgage.products', []),
    ]);
})->name('mortgage.calculator');
```

The products array is passed as a prop to the Calculator component.

### Frontend (Vue Component)
Location: `resources/js/pages/Mortgage/Calculator.vue`

**Props**:
```javascript
const props = defineProps({
    defaults: {
        type: Object,
        default: () => ({
            total_contract_price: 850000,
            age: 30,
            monthly_gross_income: 25000,
        }),
    },
    products: {
        type: Array,
        default: () => [],
    },
});
```

**State Management**:
```javascript
const selectedProduct = ref(null);
```

**Product Change Handler**:
```javascript
const onProductChange = () => {
    if (selectedProduct.value) {
        const product = props.products.find(p => p.id === selectedProduct.value);
        if (product) {
            form.total_contract_price = product.price;
            form.lending_institution = product.lending_institution;
            // Reset interest rate manual edit flag when product changes
            interestRateManuallyEdited.value = false;
        }
    }
};
```

**Template**:
```vue
<select 
    v-model="selectedProduct" 
    @change="onProductChange"
    class="w-full border-gray-300 rounded-md shadow-sm"
>
    <option :value="null">-- Select a Product --</option>
    <optgroup v-for="institution in lendingInstitutions" :key="institution.key" :label="institution.name">
        <option 
            v-for="product in products.filter(p => p.lending_institution === institution.key)" 
            :key="product.id" 
            :value="product.id"
        >
            {{ product.name }} - {{ formatCurrency(product.price) }}
        </option>
    </optgroup>
</select>
```

## Behavior Notes

### Interest Rate Auto-Calculation
When a product is selected:
1. TCP and lending institution are updated
2. The interest rate **automatically recalculates** based on the new values
3. The `interestRateManuallyEdited` flag is **reset to false**
4. This ensures the interest rate matches the selected product's lending institution

### Manual TCP Editing
After product selection:
1. User can freely edit the TCP field
2. Interest rate will **auto-update** if user hasn't manually edited it
3. Lending institution **remains** as set by product
4. User can manually change lending institution if desired

### Form Reset
When form is reset:
1. `selectedProduct` returns to `null`
2. TCP returns to default (₱850,000)
3. Lending institution returns to default (HDMF)

## Adding New Products

To add a new product:

1. **Update Configuration** (`config/mortgage.php`):
```php
'products' => [
    // ... existing products
    [
        'id' => 'product-j',
        'name' => 'Product J',
        'lending_institution' => 'hdmf',
        'price' => 3_000_000,
    ],
],
```

2. **No Frontend Changes Required** - The dropdown automatically updates

3. **Rebuild Assets**:
```bash
npm run build
```

## Future Enhancements

### Database-Backed Products
Instead of config file, store products in database:

**Benefits**:
- Admin can add/edit products via Filament
- No code deployment needed for product changes
- Support for additional product metadata (description, images, etc.)
- Product availability status (active/inactive)

**Implementation**:
1. Create `products` table migration
2. Create `Product` model
3. Update route to fetch from database
4. Create Filament resource for product management

### Product Metadata
Extend products with additional fields:
- `description` - Product details
- `project_name` - Associated project
- `location` - Property location
- `image_url` - Product image
- `status` - active, sold_out, coming_soon
- `features` - Array of features

### Product Filtering
Add filters to product selector:
- Filter by price range
- Filter by lending institution
- Search by product name
- Show only available products

### Product Details Modal
Click product name to show:
- Full product details
- Sample property images
- Amortization preview
- Feature list

## API Integration (Future)

If products move to database, create API endpoint:

```php
// routes/api.php
Route::get('/api/v1/products', [ProductController::class, 'index']);
Route::get('/api/v1/products/{id}', [ProductController::class, 'show']);
```

**Response Example**:
```json
{
    "success": true,
    "payload": [
        {
            "id": "product-a",
            "name": "Product A",
            "lending_institution": {
                "key": "hdmf",
                "name": "HDMF (Pag-IBIG)"
            },
            "price": 850000,
            "formatted_price": "₱850,000.00",
            "status": "active"
        }
    ]
}
```

## Testing

### Manual Testing Checklist
- [ ] Product selector displays all 9 products grouped by institution
- [ ] Selecting a product auto-fills TCP and institution
- [ ] TCP remains editable after selection
- [ ] Interest rate recalculates correctly
- [ ] Can switch between products seamlessly
- [ ] Can select product, then manually change institution
- [ ] Can select product, then manually adjust TCP
- [ ] Form reset clears product selection

### Automated Tests (To Be Added)
```php
// tests/Feature/ProductSelectorTest.php
test('product selector populates form correctly', function () {
    $response = $this->get('/mortgage-calculator');
    
    $response->assertInertia(fn ($page) => $page
        ->has('products', 9)
        ->where('products.0.id', 'product-a')
        ->where('products.0.price', 850000)
    );
});
```

## Related Files

- `config/mortgage.php` - Product definitions (lines 84-139)
- `routes/web.php` - Pass products to Calculator (line 17)
- `resources/js/pages/Mortgage/Calculator.vue` - Product selector UI
  - Props: lines 17-20
  - State: line 42
  - Handler: lines 60-71
  - Template: lines 316-342

## Summary

The Product Selector feature provides a **quick-fill shortcut** for mortgage calculations while maintaining full flexibility. Users can:

✅ Quickly select from 9 predefined products  
✅ Auto-fill TCP and lending institution  
✅ Still edit all fields after selection  
✅ Skip the selector and enter manually  

This feature improves **user experience** for:
- Real estate agents showcasing properties
- Buyers exploring different options
- Marketing campaigns with pre-configured products
