<script setup>
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import axios from 'axios';
import AmortizationSchedule from '@/Components/Mortgage/AmortizationSchedule.vue';
import ComparisonResults from '@/Components/Mortgage/ComparisonResults.vue';

const form = useForm({
    lending_institution: 'hdmf',
    total_contract_price: null,
    age: null,
    monthly_gross_income: null,
    co_borrower_age: null,
    co_borrower_income: null,
    additional_income: null,
    balance_payment_interest: null,
    percent_down_payment: null,
    percent_miscellaneous_fee: null,
    processing_fee: null,
    add_mri: false,
    add_fi: false,
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

const lendingInstitutions = [
    { key: 'hdmf', name: 'HDMF (Pag-IBIG)' },
    { key: 'rcbc', name: 'RCBC' },
    { key: 'cbc', name: 'CBC' },
];

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
                                <label class="block text-sm font-medium text-gray-700">Total Contract Price *</label>
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
                        <h2 class="text-xl font-semibold mb-4">Loan Parameters (Optional)</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Down Payment %</label>
                                <input
                                    v-model.number="form.percent_down_payment"
                                    type="number"
                                    step="0.01"
                                    placeholder="e.g. 0.10 for 10%"
                                    class="mt-1 w-full border-gray-300 rounded-md shadow-sm"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Miscellaneous Fee %</label>
                                <input
                                    v-model.number="form.percent_miscellaneous_fee"
                                    type="number"
                                    step="0.01"
                                    placeholder="e.g. 0.085 for 8.5%"
                                    class="mt-1 w-full border-gray-300 rounded-md shadow-sm"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Processing Fee</label>
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
                            </div>
                            <div class="pt-4 border-t">
                                <p class="text-sm text-gray-600">Loan Term</p>
                                <p class="text-lg font-semibold">{{ result.balance_payment_term }} years</p>
                            </div>
                            <div class="pt-4 border-t">
                                <p class="text-sm text-gray-600">Loanable Amount</p>
                                <p class="text-lg font-semibold">{{ formatCurrency(result.loanable_amount) }}</p>
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
                                    <span class="text-gray-600">Down Payment %:</span>
                                    <span class="font-medium">{{ formatPercent(result.percent_down_payment) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Miscellaneous Fees:</span>
                                    <span class="font-medium">{{ formatCurrency(result.miscellaneous_fees) }}</span>
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
