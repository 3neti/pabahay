<script setup>
import { ref, computed } from 'vue';
import axios from 'axios';

const props = defineProps({
    loanAmount: {
        type: Number,
        required: true,
    },
    interestRate: {
        type: Number,
        required: true,
    },
    termYears: {
        type: Number,
        required: true,
    },
    monthlyPayment: {
        type: Number,
        required: true,
    },
});

const loading = ref(false);
const schedule = ref(null);
const yearlySummary = ref(null);
const extraPaymentAnalysis = ref(null);
const error = ref(null);
const activeView = ref('monthly'); // monthly, yearly, extra
const extraPayment = ref(0);
const showExtraPaymentInput = ref(false);

const formatCurrency = (value) => {
    if (!value && value !== 0) return '₱0.00';
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
    }).format(value);
};

const formatPercent = (value) => {
    if (!value && value !== 0) return '0%';
    return `${(value * 100).toFixed(2)}%`;
};

const loadSchedule = async (view = 'monthly', includeExtra = false) => {
    loading.value = true;
    error.value = null;

    try {
        const payload = {
            loan_amount: props.loanAmount,
            interest_rate: props.interestRate,
            term_years: props.termYears,
            monthly_payment: props.monthlyPayment,
            view: view,
        };

        if (includeExtra && extraPayment.value > 0) {
            payload.extra_payment = extraPayment.value;
        }

        const response = await axios.post('/api/v1/mortgage/amortization-schedule', payload);

        if (response.data.success) {
            schedule.value = response.data.schedule;
            yearlySummary.value = response.data.yearlySummary || null;
            extraPaymentAnalysis.value = response.data.extraPaymentAnalysis || null;
        } else {
            error.value = response.data.message || 'Failed to load schedule';
        }
    } catch (err) {
        error.value = err.response?.data?.message || 'An error occurred';
        console.error('Schedule error:', err);
    } finally {
        loading.value = false;
    }
};

const calculateExtraPayment = async () => {
    if (extraPayment.value <= 0) {
        error.value = 'Please enter a valid extra payment amount';
        return;
    }
    activeView.value = 'extra';
    await loadSchedule('monthly', true);
};

const exportToCSV = () => {
    if (!schedule.value) return;

    const headers = ['Payment #', 'Payment', 'Principal', 'Interest', 'Balance'];
    const rows = schedule.value.payments.map(p => [
        p.paymentNumber,
        p.payment.toFixed(2),
        p.principal.toFixed(2),
        p.interest.toFixed(2),
        p.balance.toFixed(2),
    ]);

    const csv = [
        headers.join(','),
        ...rows.map(r => r.join(',')),
    ].join('\n');

    const blob = new Blob([csv], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'amortization-schedule.csv';
    a.click();
    URL.revokeObjectURL(url);
};

const changeView = async (view) => {
    activeView.value = view;
    if (view === 'yearly') {
        await loadSchedule('yearly');
    } else if (view === 'monthly') {
        await loadSchedule('monthly');
    }
};

// Load monthly schedule on mount
loadSchedule('monthly');
</script>

<template>
    <div class="bg-white shadow rounded-lg">
        <!-- Header -->
        <div class="border-b border-gray-200 px-6 py-4">
            <div class="flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-900">Amortization Schedule</h3>
                <button
                    @click="exportToCSV"
                    :disabled="!schedule || loading"
                    class="px-4 py-2 text-sm bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 disabled:opacity-50"
                >
                    Export CSV
                </button>
            </div>
        </div>

        <!-- Error Alert -->
        <div v-if="error" class="mx-6 mt-4 bg-red-50 border-l-4 border-red-400 p-4">
            <p class="text-sm text-red-700">{{ error }}</p>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="px-6 py-12 text-center">
            <p class="text-gray-500">Loading schedule...</p>
        </div>

        <!-- Content -->
        <div v-else-if="schedule" class="p-6">
            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-blue-50 p-4 rounded-lg">
                    <p class="text-sm text-gray-600">Total Payments</p>
                    <p class="text-xl font-bold text-gray-900">{{ schedule.totalPayments }}</p>
                </div>
                <div class="bg-green-50 p-4 rounded-lg">
                    <p class="text-sm text-gray-600">Total Amount</p>
                    <p class="text-xl font-bold text-gray-900">{{ formatCurrency(schedule.totalAmount) }}</p>
                </div>
                <div class="bg-purple-50 p-4 rounded-lg">
                    <p class="text-sm text-gray-600">Total Principal</p>
                    <p class="text-xl font-bold text-gray-900">{{ formatCurrency(schedule.totalPrincipal) }}</p>
                </div>
                <div class="bg-orange-50 p-4 rounded-lg">
                    <p class="text-sm text-gray-600">Total Interest</p>
                    <p class="text-xl font-bold text-gray-900">{{ formatCurrency(schedule.totalInterest) }}</p>
                </div>
            </div>

            <!-- Tabs -->
            <div class="border-b border-gray-200 mb-4">
                <nav class="flex gap-4">
                    <button
                        @click="changeView('monthly')"
                        :class="[
                            'px-4 py-2 text-sm font-medium border-b-2',
                            activeView === 'monthly'
                                ? 'border-blue-600 text-blue-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700'
                        ]"
                    >
                        Monthly
                    </button>
                    <button
                        @click="changeView('yearly')"
                        :class="[
                            'px-4 py-2 text-sm font-medium border-b-2',
                            activeView === 'yearly'
                                ? 'border-blue-600 text-blue-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700'
                        ]"
                    >
                        Yearly Summary
                    </button>
                    <button
                        @click="showExtraPaymentInput = true; activeView = 'extra'"
                        :class="[
                            'px-4 py-2 text-sm font-medium border-b-2',
                            activeView === 'extra'
                                ? 'border-blue-600 text-blue-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700'
                        ]"
                    >
                        Extra Payment
                    </button>
                </nav>
            </div>

            <!-- Extra Payment Input -->
            <div v-if="activeView === 'extra' && showExtraPaymentInput" class="mb-6 bg-gray-50 p-4 rounded-lg">
                <label class="block text-sm font-medium text-gray-700 mb-2">Extra Monthly Payment</label>
                <div class="flex gap-2">
                    <input
                        v-model.number="extraPayment"
                        type="number"
                        placeholder="e.g. 5000"
                        class="flex-1 border-gray-300 rounded-md shadow-sm"
                    />
                    <button
                        @click="calculateExtraPayment"
                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
                    >
                        Calculate
                    </button>
                </div>
            </div>

            <!-- Extra Payment Analysis -->
            <div v-if="extraPaymentAnalysis" class="mb-6 bg-green-50 p-4 rounded-lg border-l-4 border-green-400">
                <h4 class="font-semibold text-green-800 mb-2">Savings with Extra Payment</h4>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                    <div>
                        <p class="text-green-700">Extra Payment</p>
                        <p class="font-semibold">{{ formatCurrency(extraPaymentAnalysis.extraMonthlyPayment) }}</p>
                    </div>
                    <div>
                        <p class="text-green-700">Interest Saved</p>
                        <p class="font-semibold">{{ formatCurrency(extraPaymentAnalysis.savedInterest) }}</p>
                    </div>
                    <div>
                        <p class="text-green-700">Months Saved</p>
                        <p class="font-semibold">{{ extraPaymentAnalysis.savedMonths }} months</p>
                    </div>
                    <div>
                        <p class="text-green-700">New Term</p>
                        <p class="font-semibold">{{ (extraPaymentAnalysis.newTotalPayments / 12).toFixed(1) }} years</p>
                    </div>
                </div>
            </div>

            <!-- Monthly View -->
            <div v-if="activeView === 'monthly'" class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Payment</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Principal</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Interest</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Balance</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="payment in schedule.payments" :key="payment.paymentNumber" class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm text-gray-900">{{ payment.paymentNumber }}</td>
                            <td class="px-4 py-3 text-sm text-right text-gray-900">{{ formatCurrency(payment.payment) }}</td>
                            <td class="px-4 py-3 text-sm text-right text-blue-600">{{ formatCurrency(payment.principal) }}</td>
                            <td class="px-4 py-3 text-sm text-right text-orange-600">{{ formatCurrency(payment.interest) }}</td>
                            <td class="px-4 py-3 text-sm text-right font-medium text-gray-900">{{ formatCurrency(payment.balance) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Yearly Summary View -->
            <div v-if="activeView === 'yearly' && yearlySummary" class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Year</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total Payment</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Principal</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Interest</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ending Balance</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="year in yearlySummary" :key="year.year" class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm font-medium text-gray-900">Year {{ year.year }}</td>
                            <td class="px-4 py-3 text-sm text-right text-gray-900">{{ formatCurrency(year.totalPayment) }}</td>
                            <td class="px-4 py-3 text-sm text-right text-blue-600">{{ formatCurrency(year.principal) }}</td>
                            <td class="px-4 py-3 text-sm text-right text-orange-600">{{ formatCurrency(year.interest) }}</td>
                            <td class="px-4 py-3 text-sm text-right font-medium text-gray-900">{{ formatCurrency(year.endingBalance) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
