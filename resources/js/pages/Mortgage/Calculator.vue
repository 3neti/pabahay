<script setup>
import { ref, computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import axios from 'axios';
import AmortizationSchedule from '@/Components/Mortgage/AmortizationSchedule.vue';
import ComparisonResults from '@/Components/Mortgage/ComparisonResults.vue';

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

// Interest rate calculation logic
const calculateInterestRate = (institution, tcp) => {
    // Market segment rates (used for HDMF or as fallback)
    const getMarketRate = (price) => {
        if (price <= 750000) return 0.03;  // 3%
        if (price <= 850000) return 0.0625;  // 6.25%
        return 0.0625;  // 6.25% for > 850k
    };

    // Lending institution default rates
    const institutionRates = {
        'hdmf': getMarketRate(tcp),  // HDMF uses market segment rates
        'rcbc': 0.08,  // 8%
        'cbc': 0.07,   // 7%
    };

    return institutionRates[institution] || 0.0625;
};

const selectedProduct = ref(null);

const form = useForm({
    lending_institution: 'hdmf',
    total_contract_price: props.defaults.total_contract_price,
    age: props.defaults.age,
    monthly_gross_income: props.defaults.monthly_gross_income,
    co_borrower_age: null,
    co_borrower_income: null,
    additional_income: null,
    balance_payment_interest: calculateInterestRate('hdmf', props.defaults.total_contract_price),
    percent_down_payment: null,
    percent_miscellaneous_fee: null,
    processing_fee: null,
    add_mri: false,
    add_fi: false,
    desired_loan_term: null, // Optional: user can choose shorter term than max
});

const lendingInstitutions = [
    { key: 'hdmf', name: 'HDMF (Pag-IBIG)' },
    { key: 'rcbc', name: 'RCBC' },
    { key: 'cbc', name: 'CBC' },
];

// Product selector handler
const onProductChange = () => {
    if (selectedProduct.value) {
        const product = props.products.find(p => p.id === selectedProduct.value);
        if (product) {
            // Populate basic product info
            form.total_contract_price = product.price;
            form.lending_institution = product.lending_institution;
            
            // Populate lending institution details if available
            if (product.institution_details) {
                form.balance_payment_interest = product.institution_details.interest_rate;
                form.percent_down_payment = product.institution_details.percent_dp;
                form.percent_miscellaneous_fee = product.institution_details.percent_mf;
                form.processing_fee = product.institution_details.processing_fee;
                form.add_mri = product.institution_details.default_add_mri;
                form.add_fi = product.institution_details.default_add_fi;
            }
            
            // Clear computation results when product changes
            result.value = null;
            error.value = null;
            
            // Reset manual edit flags when product changes
            interestRateManuallyEdited.value = false;
            mfManuallyEdited.value = false;
            // Disable auto-select when user manually changes product
            autoSelectEnabled.value = false;
        }
    }
};

// Track if user manually edited interest rate or MF
const interestRateManuallyEdited = ref(false);
const mfManuallyEdited = ref(false);

// Calculate MF percent based on lending institution
const calculateMFPercent = (institution) => {
    const institutionMF = {
        'hdmf': 0.0,    // 0%
        'rcbc': 0.085,  // 8.5%
        'cbc': 0.085,   // 8.5%
    };
    return institutionMF[institution] || 0.0;
};

// Watch for changes in TCP or lending institution to auto-update interest rate
watch(
    [() => form.total_contract_price, () => form.lending_institution],
    ([newTCP, newInstitution]) => {
        // Only auto-update if user hasn't manually edited it
        if (!interestRateManuallyEdited.value && newTCP && newInstitution) {
            form.balance_payment_interest = calculateInterestRate(newInstitution, newTCP);
        }
    }
);

// Watch for lending institution changes to auto-update MF
watch(
    () => form.lending_institution,
    (newInstitution) => {
        // Only auto-update if user hasn't manually edited it
        if (!mfManuallyEdited.value && newInstitution) {
            form.percent_miscellaneous_fee = calculateMFPercent(newInstitution);
        }
    },
    { immediate: true } // Calculate on mount
);

// Mark as manually edited when user changes the interest rate directly
const onInterestRateChange = () => {
    interestRateManuallyEdited.value = true;
};

// Mark as manually edited when user changes MF directly
const onMFChange = () => {
    mfManuallyEdited.value = true;
};

// Calculate maximum loan term based on age and lending institution
const maxLoanTerm = computed(() => {
    const age = form.age;
    const coBorrowerAge = form.co_borrower_age;
    const institution = form.lending_institution;
    
    if (!age || age < 18) {
        return 30; // Default
    }
    
    // Get lending institution parameters
    const institutionParams = {
        'hdmf': { maxPayingAge: 70, maxTerm: 30, offset: 0 },
        'rcbc': { maxPayingAge: 65, maxTerm: 20, offset: -1 },
        'cbc': { maxPayingAge: 65, maxTerm: 20, offset: -1 },
    };
    
    const params = institutionParams[institution] || institutionParams['hdmf'];
    
    // Use oldest borrower's age
    const effectiveAge = (coBorrowerAge && coBorrowerAge > age) ? coBorrowerAge : age;
    
    // Calculate: min(floor((maxPayingAge + offset) - age), maxTerm)
    // Note: Backend may use fractional age (e.g., 50.5 years), which can result in
    // one year less than shown here. This is expected behavior.
    const limit = params.maxPayingAge + params.offset;
    const calculatedTerm = Math.floor(limit - effectiveAge);
    
    return Math.max(5, Math.min(calculatedTerm, params.maxTerm)); // Between 5 and max
});

// State for affordability calculation
const affordabilityLoading = ref(false);
const calculateMaxAffordablePrice = ref(0);
let affordabilityDebounceTimer = null;

// State for product auto-selection
const productSelectionLoading = ref(false);
const recommendedProduct = ref(null);
const autoSelectEnabled = ref(true);
let productSelectionDebounceTimer = null;

// Debounced function to fetch affordability from backend
const fetchAffordability = async () => {
    const age = form.age;
    const gmi = form.monthly_gross_income;
    
    if (!age || !gmi || age < 18 || gmi <= 0) {
        calculateMaxAffordablePrice.value = 0;
        return;
    }
    
    affordabilityLoading.value = true;
    
    try {
        const response = await axios.post('/api/v1/mortgage/affordability', {
            lending_institution: form.lending_institution,
            age: age,
            monthly_gross_income: gmi,
            additional_income: form.additional_income || null,
            co_borrower_age: form.co_borrower_age || null,
            co_borrower_income: form.co_borrower_income || null,
            down_payment_available: 0, // Assume 0 down payment for filtering
            monthly_debts: 0, // No debts for simple filtering
            loan_term: form.desired_loan_term || null, // Use desired term if specified
        });
        
        if (response.data.success) {
            calculateMaxAffordablePrice.value = Math.floor(response.data.data.max_home_price);
        }
    } catch (err) {
        console.error('Affordability calculation error:', err);
        calculateMaxAffordablePrice.value = 0;
    } finally {
        affordabilityLoading.value = false;
    }
};

// Debounced function to auto-select best product
const autoSelectProduct = async (forceRun = false) => {
    const age = form.age;
    const gmi = form.monthly_gross_income;
    
    if (!age || !gmi || age < 18 || gmi <= 0) {
        recommendedProduct.value = null;
        return;
    }
    
    // Skip if auto-select disabled unless forced (manual button click)
    if (!forceRun && !autoSelectEnabled.value) {
        recommendedProduct.value = null;
        return;
    }
    
    productSelectionLoading.value = true;
    
    try {
        const response = await axios.post('/api/v1/mortgage/product/select', {
            age: age,
            monthly_gross_income: gmi,
            return_top_n: 3,
        });
        
        if (response.data.success && response.data.selected_product) {
            recommendedProduct.value = response.data.selected_product;
            
            // Auto-select the product in the dropdown
            selectedProduct.value = response.data.selected_product.product_id;
            
            // Trigger product change to update form fields
            onProductChange();
        } else {
            recommendedProduct.value = null;
        }
    } catch (err) {
        console.error('Product selection error:', err);
        recommendedProduct.value = null;
    } finally {
        productSelectionLoading.value = false;
    }
};

// Watch for changes in buyer details and debounce API call
watch(
    [() => form.age, () => form.monthly_gross_income, () => form.additional_income, 
     () => form.co_borrower_age, () => form.co_borrower_income, () => form.lending_institution],
    () => {
        // Clear existing timer
        if (affordabilityDebounceTimer) {
            clearTimeout(affordabilityDebounceTimer);
        }
        
        // Set new timer for 500ms debounce
        affordabilityDebounceTimer = setTimeout(() => {
            fetchAffordability();
        }, 500);
    },
    { immediate: true } // Calculate on mount
);

// Watch for ANY form field change that affects computation and clear results
watch(
    [
        () => form.lending_institution,
        () => form.total_contract_price,
        () => form.age,
        () => form.monthly_gross_income,
        () => form.co_borrower_age,
        () => form.co_borrower_income,
        () => form.additional_income,
        () => form.balance_payment_interest,
        () => form.percent_down_payment,
        () => form.percent_miscellaneous_fee,
        () => form.processing_fee,
        () => form.add_mri,
        () => form.add_fi,
        () => form.desired_loan_term,
    ],
    () => {
        // Clear computation results whenever any input changes
        if (result.value !== null) {
            result.value = null;
            error.value = null;
        }
    }
);

// Manual product recommendation (no auto-select)
const getProductRecommendation = async () => {
    // Force run even if autoSelectEnabled is false
    await autoSelectProduct(true);
};

// Filter products by affordability
const affordableProducts = computed(() => {
    const maxPrice = calculateMaxAffordablePrice.value;
    
    if (maxPrice === 0) {
        return props.products; // Show all if no buyer info yet
    }
    
    return props.products.filter(product => product.price <= maxPrice);
});

// Group products by lending institution
const groupedAffordableProducts = computed(() => {
    const grouped = {};
    
    lendingInstitutions.forEach(inst => {
        grouped[inst.key] = affordableProducts.value.filter(p => p.lending_institution === inst.key);
    });
    
    return grouped;
});

const computing = ref(false);
const result = ref(null);
const error = ref(null);
const saving = ref(false);
const savedProfile = ref(null);
const showSaveModal = ref(false);
const showLookupModal = ref(false);
const lookupCode = ref('');
const lookingUp = ref(false);
const comparing = ref(false);
const comparisonResults = ref(null);
const showComparison = ref(false);
const saveForm = useForm({
    email: '',
    name: '',
    send_email: false,
});

const formatCurrency = (value) => {
    if (!value) return '₱0.00';
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
    }).format(value);
};

const formatPercent = (value) => {
    if (!value) return '0%';
    return `${(value * 100).toFixed(2)}%`;
};

const compute = async () => {
    computing.value = true;
    error.value = null;
    result.value = null;

    try {
        const response = await axios.post('/api/v1/mortgage-compute', form.data());
        
        if (response.data.success) {
            result.value = response.data.payload;
        } else {
            error.value = response.data.message || 'Computation failed';
        }
    } catch (err) {
        error.value = err.response?.data?.message || 'An error occurred. Please try again.';
        console.error('Computation error:', err);
    } finally {
        computing.value = false;
    }
};

const reset = () => {
    form.reset();
    result.value = null;
    error.value = null;
    savedProfile.value = null;
};

const saveCalculation = async () => {
    saving.value = true;
    error.value = null;

    try {
        const response = await axios.post('/api/v1/mortgage/loan-profiles', {
            ...form.data(),
            ...result.value,
            borrower_name: saveForm.email ? saveForm.name : null,
            borrower_email: saveForm.email || null,
            send_email: saveForm.send_email,
        });

        if (response.data.success) {
            savedProfile.value = response.data.payload;
            showSaveModal.value = false;
            saveForm.reset();
        } else {
            error.value = response.data.message || 'Failed to save calculation';
        }
    } catch (err) {
        error.value = err.response?.data?.message || 'An error occurred while saving.';
        console.error('Save error:', err);
    } finally {
        saving.value = false;
    }
};

const lookupProfile = async () => {
    lookingUp.value = true;
    error.value = null;

    try {
        const response = await axios.get(`/api/v1/mortgage/loan-profiles/${lookupCode.value}`);

        if (response.data.success) {
            const profile = response.data.payload;
            
            // Populate form with saved data
            form.lending_institution = profile.lending_institution;
            form.total_contract_price = profile.total_contract_price;
            form.age = profile.age;
            form.monthly_gross_income = profile.monthly_gross_income;
            form.co_borrower_age = profile.co_borrower_age;
            form.co_borrower_income = profile.co_borrower_income;
            form.additional_income = profile.additional_income;
            form.percent_down_payment = profile.percent_down_payment;
            form.percent_miscellaneous_fee = profile.percent_miscellaneous_fee;
            form.processing_fee = profile.processing_fee;
            form.add_mri = profile.add_mri;
            form.add_fi = profile.add_fi;
            
            // Set result
            result.value = {
                monthly_amortization: profile.monthly_amortization,
                balance_payment_term: profile.balance_payment_term,
                loanable_amount: profile.loanable_amount,
                required_equity: profile.required_equity,
                interest_rate: profile.interest_rate,
                percent_down_payment: profile.percent_down_payment,
                miscellaneous_fees: profile.miscellaneous_fees,
                cash_out: profile.cash_out,
                monthly_disposable_income: profile.monthly_disposable_income,
                income_gap: profile.income_gap,
                percent_down_payment_remedy: profile.percent_down_payment_remedy,
                qualification: profile.qualification,
            };
            
            savedProfile.value = profile;
            showLookupModal.value = false;
            lookupCode.value = '';
        } else {
            error.value = response.data.message || 'Profile not found';
        }
    } catch (err) {
        error.value = err.response?.data?.message || 'Failed to retrieve profile.';
        console.error('Lookup error:', err);
    } finally {
        lookingUp.value = false;
    }
};

const closeSavedAlert = () => {
    savedProfile.value = null;
};

const compareInstitutions = async () => {
    comparing.value = true;
    error.value = null;

    try {
        const response = await axios.post('/api/v1/mortgage/compare', form.data());

        if (response.data.success) {
            comparisonResults.value = response.data;
            showComparison.value = true;
        } else {
            error.value = response.data.message || 'Comparison failed';
        }
    } catch (err) {
        error.value = err.response?.data?.message || 'An error occurred during comparison.';
        console.error('Comparison error:', err);
    } finally {
        comparing.value = false;
    }
};

const closeComparison = () => {
    showComparison.value = false;
};
</script>

<template>
    <div class="min-h-screen bg-gray-50 py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8 flex justify-between items-start">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Mortgage Calculator</h1>
                    <p class="mt-2 text-gray-600">Calculate your mortgage based on various lending institutions</p>
                </div>
                <button
                    @click="showLookupModal = true"
                    class="px-4 py-2 text-sm border border-gray-300 rounded-md hover:bg-gray-50"
                >
                    Look Up Saved Calculation
                </button>
            </div>

            <!-- Error Alert -->
            <div v-if="error" class="mb-6 bg-red-50 border-l-4 border-red-400 p-4">
                <div class="flex">
                    <div class="ml-3">
                        <p class="text-sm text-red-700">{{ error }}</p>
                    </div>
                </div>
            </div>

            <!-- Success Alert for Saved Profile -->
            <div v-if="savedProfile" class="mb-6 bg-green-50 border-l-4 border-green-400 p-4">
                <div class="flex justify-between items-start">
                    <div class="ml-3">
                        <p class="text-sm font-medium text-green-800">Calculation Saved Successfully!</p>
                        <p class="text-sm text-green-700 mt-1">
                            Reference Code: <span class="font-mono font-bold">{{ savedProfile.reference_code }}</span>
                        </p>
                        <p v-if="savedProfile.borrower_email" class="text-sm text-green-700 mt-1">
                            A copy has been sent to {{ savedProfile.borrower_email }}
                        </p>
                    </div>
                    <button @click="closeSavedAlert" class="text-green-500 hover:text-green-700">
                        ✕
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Input Form -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Buyer Details -->
                    <div class="bg-white shadow rounded-lg p-6">
                        <h2 class="text-xl font-semibold mb-4">Buyer Details</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Age *</label>
                                <input
                                    v-model.number="form.age"
                                    type="number"
                                    placeholder="e.g. 35"
                                    class="mt-1 w-full border-gray-300 rounded-md shadow-sm"
                                    required
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Monthly Gross Income *</label>
                                <input
                                    v-model.number="form.monthly_gross_income"
                                    type="number"
                                    placeholder="e.g. 25000"
                                    class="mt-1 w-full border-gray-300 rounded-md shadow-sm"
                                    required
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Additional Income</label>
                                <input
                                    v-model.number="form.additional_income"
                                    type="number"
                                    placeholder="Optional"
                                    class="mt-1 w-full border-gray-300 rounded-md shadow-sm"
                                />
                            </div>
                        </div>
                        
                        <!-- Affordability Indicator -->
                        <div v-if="calculateMaxAffordablePrice > 0" class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-md">
                            <p class="text-sm text-blue-800">
                                <span class="font-semibold">Estimated Maximum Affordable Price:</span> 
                                {{ formatCurrency(calculateMaxAffordablePrice) }}
                            </p>
                            <p class="text-xs text-blue-600 mt-1">
                                Based on {{ form.age }} years old with {{ formatCurrency(form.monthly_gross_income) }}/month income
                            </p>
                        </div>
                        
                        <!-- Loan Term Section -->
                        <div v-if="form.age && form.monthly_gross_income" class="mt-4 p-3 bg-gray-50 border border-gray-200 rounded-md">
                            <div class="flex items-center justify-between mb-2">
                                <div>
                                    <p class="text-sm font-semibold text-gray-800">Maximum Loan Term</p>
                                    <p class="text-xs text-gray-600">
                                        Based on age {{ form.co_borrower_age && form.co_borrower_age > form.age ? form.co_borrower_age : form.age }} 
                                        and {{ form.lending_institution.toUpperCase() }}
                                    </p>
                                </div>
                                <p class="text-2xl font-bold text-gray-900">{{ maxLoanTerm }} years</p>
                            </div>
                            
                            <div class="mt-3">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Desired Loan Term (Optional)
                                    <span class="text-xs text-gray-500">- Choose a shorter term for less interest</span>
                                </label>
                                <div class="flex items-center gap-3">
                                    <input
                                        v-model.number="form.desired_loan_term"
                                        type="number"
                                        :min="5"
                                        :max="maxLoanTerm"
                                        placeholder="Leave blank to use maximum"
                                        class="flex-1 border-gray-300 rounded-md shadow-sm text-sm"
                                    />
                                    <span class="text-sm text-gray-600">years</span>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">
                                    💡 Shorter term = Higher monthly payment, but you'll pay less interest overall.
                                    Range: 5 to {{ maxLoanTerm }} years.
                                </p>
                                <p v-if="form.desired_loan_term && (form.desired_loan_term < 5 || form.desired_loan_term > maxLoanTerm)" 
                                   class="text-xs text-red-600 mt-1">
                                    ⚠️ Please enter a term between 5 and {{ maxLoanTerm }} years.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Product Selector -->
                    <div v-if="products.length > 0" class="bg-white shadow rounded-lg p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h2 class="text-xl font-semibold">
                                    Product Selection 
                                    <span v-if="calculateMaxAffordablePrice > 0" class="text-sm font-normal text-gray-500">
                                        - Showing {{ affordableProducts.length }} of {{ products.length }} products
                                    </span>
                                </h2>
                            </div>
                            <button
                                @click="getProductRecommendation"
                                :disabled="!form.age || !form.monthly_gross_income || form.age < 18 || form.monthly_gross_income <= 0 || productSelectionLoading"
                                class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 disabled:bg-gray-300 disabled:cursor-not-allowed flex items-center gap-2"
                            >
                                <svg v-if="productSelectionLoading" class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span v-if="productSelectionLoading">Analyzing...</span>
                                <span v-else>💡 Get Product Recommendation</span>
                            </button>
                        </div>
                        
                        <!-- Recommendation Result -->
                        <div v-if="recommendedProduct" class="mb-4 p-4 bg-green-50 border-l-4 border-green-400">
                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3 flex-1">
                                    <h3 class="text-sm font-medium text-green-800">Recommended Product</h3>
                                    <div class="mt-2 text-sm text-green-700">
                                        <p class="font-semibold">{{ recommendedProduct.product_name }}</p>
                                        <p class="text-xs mt-1">{{ formatCurrency(recommendedProduct.price) }} • {{ recommendedProduct.lending_institution.toUpperCase() }}</p>
                                        <p class="text-xs mt-1 italic">{{ recommendedProduct.reasoning }}</p>
                                    </div>
                                </div>
                                <button @click="recommendedProduct = null" class="flex-shrink-0 ml-4 text-green-500 hover:text-green-700">
                                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                        
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">
                                Select a Product
                                <span class="text-xs text-gray-500">(filtered by your budget, auto-fills price and lending institution)</span>
                            </label>
                            <select 
                                v-model="selectedProduct" 
                                @change="onProductChange"
                                class="w-full border-gray-300 rounded-md shadow-sm"
                            >
                                <option :value="null">-- Select a Product --</option>
                                <template v-for="institution in lendingInstitutions" :key="institution.key">
                                    <optgroup 
                                        v-if="groupedAffordableProducts[institution.key]?.length > 0"
                                        :label="institution.name"
                                    >
                                        <option 
                                            v-for="product in groupedAffordableProducts[institution.key]" 
                                            :key="product.id" 
                                            :value="product.id"
                                        >
                                            {{ product.name }} - {{ formatCurrency(product.price) }}
                                        </option>
                                    </optgroup>
                                </template>
                            </select>
                            <p v-if="calculateMaxAffordablePrice > 0 && affordableProducts.length === 0" class="text-xs text-red-600">
                                ⚠️ No products match your current budget. Try increasing your income or consider a co-borrower.
                            </p>
                            <p v-else class="text-xs text-gray-500">
                                Or manually select lending institution and enter price below
                            </p>
                        </div>
                    </div>

                    <!-- Lending Institution -->
                    <div class="bg-white shadow rounded-lg p-6">
                        <h2 class="text-xl font-semibold mb-4">Lending Institution</h2>
                        <select v-model="form.lending_institution" class="w-full border-gray-300 rounded-md shadow-sm">
                            <option v-for="inst in lendingInstitutions" :key="inst.key" :value="inst.key">
                                {{ inst.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Property Details -->
                    <div class="bg-white shadow rounded-lg p-6">
                        <h2 class="text-xl font-semibold mb-4">Property Details</h2>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">
                                    Total Contract Price *
                                    <span class="text-xs text-gray-500">(editable)</span>
                                </label>
                                <input
                                    v-model.number="form.total_contract_price"
                                    type="number"
                                    placeholder="e.g. 1000000"
                                    class="mt-1 w-full border-gray-300 rounded-md shadow-sm"
                                    required
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Co-Borrower Details -->
                    <div class="bg-white shadow rounded-lg p-6">
                        <h2 class="text-xl font-semibold mb-4">Co-Borrower Details (Optional)</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Co-Borrower Age</label>
                                <input
                                    v-model.number="form.co_borrower_age"
                                    type="number"
                                    placeholder="Optional"
                                    class="mt-1 w-full border-gray-300 rounded-md shadow-sm"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Co-Borrower Income</label>
                                <input
                                    v-model.number="form.co_borrower_income"
                                    type="number"
                                    placeholder="Optional"
                                    class="mt-1 w-full border-gray-300 rounded-md shadow-sm"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Loan Parameters -->
                    <div class="bg-white shadow rounded-lg p-6">
                        <h2 class="text-xl font-semibold mb-4">Loan Parameters</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">
                                    Interest Rate % 
                                    <span class="text-xs text-gray-500">(auto-calculated, editable)</span>
                                </label>
                                <input
                                    v-model.number="form.balance_payment_interest"
                                    @input="onInterestRateChange"
                                    type="number"
                                    step="0.0001"
                                    placeholder="e.g. 0.0625 for 6.25%"
                                    class="mt-1 w-full border-gray-300 rounded-md shadow-sm"
                                />
                                <p class="mt-1 text-xs text-gray-500">
                                    {{ form.balance_payment_interest ? formatPercent(form.balance_payment_interest) : '' }}
                                </p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Down Payment % (Optional)</label>
                                <input
                                    v-model.number="form.percent_down_payment"
                                    type="number"
                                    step="0.01"
                                    placeholder="e.g. 0.10 for 10%"
                                    class="mt-1 w-full border-gray-300 rounded-md shadow-sm"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">
                                    Miscellaneous Fee % 
                                    <span class="text-xs text-gray-500">(auto-calculated, editable)</span>
                                </label>
                                <input
                                    v-model.number="form.percent_miscellaneous_fee"
                                    @input="onMFChange"
                                    type="number"
                                    step="0.0001"
                                    placeholder="e.g. 0.085 for 8.5%"
                                    class="mt-1 w-full border-gray-300 rounded-md shadow-sm"
                                />
                                <p class="mt-1 text-xs text-gray-500">
                                    {{ form.percent_miscellaneous_fee ? formatPercent(form.percent_miscellaneous_fee) : '0%' }}
                                    <span class="text-gray-400">• Added to loan and amortized over term</span>
                                </p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Processing Fee (Optional)</label>
                                <input
                                    v-model.number="form.processing_fee"
                                    type="number"
                                    placeholder="Optional"
                                    class="mt-1 w-full border-gray-300 rounded-md shadow-sm"
                                />
                            </div>
                        </div>
                        <div class="mt-4 space-y-2">
                            <label class="flex items-center">
                                <input v-model="form.add_mri" type="checkbox" class="rounded border-gray-300" />
                                <span class="ml-2 text-sm text-gray-700">Add MRI (Mortgage Redemption Insurance)</span>
                            </label>
                            <label class="flex items-center">
                                <input v-model="form.add_fi" type="checkbox" class="rounded border-gray-300" />
                                <span class="ml-2 text-sm text-gray-700">Add FI (Fire Insurance)</span>
                            </label>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex gap-4">
                        <button
                            @click="compute"
                            :disabled="computing"
                            class="flex-1 bg-blue-600 text-white px-6 py-3 rounded-md hover:bg-blue-700 disabled:opacity-50"
                        >
                            {{ computing ? 'Computing...' : 'Compute Mortgage' }}
                        </button>
                        <button
                            @click="compareInstitutions"
                            :disabled="comparing"
                            class="flex-1 bg-purple-600 text-white px-6 py-3 rounded-md hover:bg-purple-700 disabled:opacity-50"
                        >
                            {{ comparing ? 'Comparing...' : 'Compare All' }}
                        </button>
                        <button
                            @click="reset"
                            class="px-6 py-3 border border-gray-300 rounded-md hover:bg-gray-50"
                        >
                            Reset
                        </button>
                    </div>
                </div>

                <!-- Results Panel -->
                <div v-if="result" class="lg:col-span-1">
                    <div class="bg-white shadow rounded-lg p-6 sticky top-4">
                        <h2 class="text-xl font-semibold mb-4">Computation Results</h2>

                        <!-- Qualification Status -->
                        <div :class="[
                            'p-4 rounded-lg mb-6',
                            result.qualification.qualifies ? 'bg-green-50 border-l-4 border-green-400' : 'bg-red-50 border-l-4 border-red-400'
                        ]">
                            <p :class="[
                                'font-semibold',
                                result.qualification.qualifies ? 'text-green-800' : 'text-red-800'
                            ]">
                                {{ result.qualification.qualifies ? '✓ Loan Qualified' : '✗ Not Qualified' }}
                            </p>
                            <p :class="[
                                'text-sm mt-1',
                                result.qualification.qualifies ? 'text-green-700' : 'text-red-700'
                            ]">
                                {{ result.qualification.reason }}
                            </p>
                        </div>

                        <!-- Key Figures -->
                        <div class="space-y-4">
                            <div>
                                <p class="text-sm text-gray-600">Monthly Amortization</p>
                                <p class="text-2xl font-bold text-gray-900">{{ formatCurrency(result.monthly_amortization) }}</p>
                                
                                <!-- Amortization Breakdown -->
                                <div v-if="result.monthly_mri > 0 || result.monthly_fi > 0" class="mt-3 p-3 bg-gray-50 rounded text-xs space-y-1">
                                    <p class="font-medium text-gray-700 mb-2">Breakdown:</p>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Base Amortization:</span>
                                        <span class="font-medium">{{ formatCurrency(result.base_amortization) }}</span>
                                    </div>
                                    <div v-if="result.monthly_mri > 0" class="flex justify-between">
                                        <span class="text-gray-600">+ MRI:</span>
                                        <span class="font-medium">{{ formatCurrency(result.monthly_mri) }}</span>
                                    </div>
                                    <div v-if="result.monthly_fi > 0" class="flex justify-between">
                                        <span class="text-gray-600">+ FI:</span>
                                        <span class="font-medium">{{ formatCurrency(result.monthly_fi) }}</span>
                                    </div>
                                    <div class="pt-2 border-t border-gray-300 flex justify-between">
                                        <span class="text-gray-700 font-semibold">Total:</span>
                                        <span class="font-bold">{{ formatCurrency(result.monthly_amortization) }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="pt-4 border-t">
                                <p class="text-sm text-gray-600">Loan Term</p>
                                <p class="text-lg font-semibold">{{ result.balance_payment_term }} years</p>
                            </div>
                            <div class="pt-4 border-t">
                                <p class="text-sm text-gray-600">Down Payment</p>
                                <p class="text-lg font-semibold">{{ formatCurrency(result.down_payment_amount) }}</p>
                                <p class="text-xs text-gray-500 mt-1">{{ formatPercent(result.percent_down_payment) }} of TCP, paid upfront</p>
                            </div>
                            <div class="pt-4 border-t">
                                <p class="text-sm text-gray-600">Base Loan Amount</p>
                                <p class="text-lg font-semibold">{{ formatCurrency(result.base_loan_amount) }}</p>
                                <p class="text-xs text-gray-500 mt-1">TCP minus down payment</p>
                            </div>
                            <div class="pt-4 border-t">
                                <p class="text-sm text-gray-600">Miscellaneous Fees</p>
                                <p class="text-lg font-semibold">{{ formatCurrency(result.miscellaneous_fees) }}</p>
                                <p class="text-xs text-gray-500 mt-1">{{ formatPercent(result.percent_miscellaneous_fees) }} of TCP, added to loan</p>
                            </div>
                            <div class="pt-4 border-t bg-blue-50 -mx-6 px-6 py-4">
                                <p class="text-sm text-gray-600 font-medium">Total Amount Financed</p>
                                <p class="text-xl font-bold text-blue-900">{{ formatCurrency(result.loanable_amount) }}</p>
                                <p class="text-xs text-gray-500 mt-1">Your actual loan (Base + MF), amortized over {{ result.balance_payment_term }} years</p>
                            </div>
                            <div class="pt-4 border-t">
                                <p class="text-sm text-gray-600">Required Equity</p>
                                <p class="text-lg font-semibold">{{ formatCurrency(result.required_equity) }}</p>
                            </div>
                            <div v-if="!result.qualification.qualifies" class="pt-4 border-t">
                                <p class="text-sm text-gray-600">Income Gap</p>
                                <p class="text-lg font-semibold text-red-600">{{ formatCurrency(result.income_gap) }}</p>
                            </div>
                            <div v-if="!result.qualification.qualifies" class="pt-4 border-t">
                                <p class="text-sm text-gray-600">Suggested Down Payment</p>
                                <p class="text-lg font-semibold">{{ formatPercent(result.percent_down_payment_remedy) }}</p>
                            </div>
                        </div>

                        <!-- Additional Details -->
                        <details class="mt-6">
                            <summary class="cursor-pointer text-sm font-medium text-gray-700">View All Details</summary>
                            <div class="mt-4 space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Interest Rate:</span>
                                    <span class="font-medium">{{ formatPercent(result.interest_rate) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Down Payment:</span>
                                    <span class="font-medium">{{ formatCurrency(result.down_payment_amount) }} ({{ formatPercent(result.percent_down_payment) }})</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Total Property Cost:</span>
                                    <span class="font-medium">{{ formatCurrency(result.total_property_cost) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Cash Out:</span>
                                    <span class="font-medium">{{ formatCurrency(result.cash_out) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Disposable Income:</span>
                                    <span class="font-medium">{{ formatCurrency(result.monthly_disposable_income) }}</span>
                                </div>
                            </div>
                        </details>

                        <!-- Save Calculation Button -->
                        <button
                            v-if="!savedProfile"
                            @click="showSaveModal = true"
                            class="w-full mt-6 px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700"
                        >
                            Save This Calculation
                        </button>
                    </div>
                </div>
            </div>

            <!-- Amortization Schedule Section -->
            <div v-if="result" class="mt-8">
                <AmortizationSchedule
                    :loan-amount="result.loanable_amount"
                    :interest-rate="result.interest_rate"
                    :term-years="result.balance_payment_term"
                    :monthly-payment="result.monthly_amortization"
                />
            </div>

            <!-- Save Modal -->
            <div v-if="showSaveModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
                <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
                    <h3 class="text-lg font-semibold mb-4">Save Your Calculation</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Your Name (Optional)</label>
                            <input
                                v-model="saveForm.name"
                                type="text"
                                placeholder="John Doe"
                                class="w-full border-gray-300 rounded-md shadow-sm"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email (Optional)</label>
                            <input
                                v-model="saveForm.email"
                                type="email"
                                placeholder="john@example.com"
                                class="w-full border-gray-300 rounded-md shadow-sm"
                            />
                        </div>
                        <label class="flex items-center">
                            <input v-model="saveForm.send_email" type="checkbox" class="rounded border-gray-300" />
                            <span class="ml-2 text-sm text-gray-700">Email me a copy of this calculation</span>
                        </label>
                    </div>
                    <div class="flex gap-3 mt-6">
                        <button
                            @click="saveCalculation"
                            :disabled="saving"
                            class="flex-1 bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 disabled:opacity-50"
                        >
                            {{ saving ? 'Saving...' : 'Save' }}
                        </button>
                        <button
                            @click="showSaveModal = false"
                            class="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50"
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            </div>

            <!-- Lookup Modal -->
            <div v-if="showLookupModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
                <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
                    <h3 class="text-lg font-semibold mb-4">Look Up Saved Calculation</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Reference Code</label>
                            <input
                                v-model="lookupCode"
                                type="text"
                                placeholder="Enter your reference code"
                                class="w-full border-gray-300 rounded-md shadow-sm"
                                @keyup.enter="lookupProfile"
                            />
                        </div>
                    </div>
                    <div class="flex gap-3 mt-6">
                        <button
                            @click="lookupProfile"
                            :disabled="lookingUp || !lookupCode"
                            class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 disabled:opacity-50"
                        >
                            {{ lookingUp ? 'Looking up...' : 'Look Up' }}
                        </button>
                        <button
                            @click="showLookupModal = false"
                            class="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50"
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            </div>

            <!-- Comparison Modal -->
            <ComparisonResults
                v-if="showComparison && comparisonResults"
                :comparisons="comparisonResults.comparisons"
                :best-options="comparisonResults.bestOptions"
                @close="closeComparison"
            />
        </div>
    </div>
</template>
