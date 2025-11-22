<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    comparisons: {
        type: Array,
        required: true,
    },
    bestOptions: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['close']);

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

const calculateTotalInterest = (result) => {
    const totalPayment = result.monthly_amortization * (result.balance_payment_term * 12);
    return totalPayment - result.loanable_amount;
};

const isBest = (institution, category) => {
    return props.bestOptions[category]?.institution === institution;
};

const exportToCSV = () => {
    const headers = [
        'Institution',
        'Qualifies',
        'Monthly Payment',
        'Loan Term',
        'Interest Rate',
        'Loanable Amount',
        'Required Equity',
        'Cash Out',
        'Total Interest',
    ];

    const rows = props.comparisons.map(comp => {
        if (comp.error) {
            return [comp.institutionName, 'Error', '', '', '', '', '', '', ''];
        }

        const result = comp.result;
        return [
            comp.institutionName,
            result.qualification.qualifies ? 'Yes' : 'No',
            result.monthly_amortization.toFixed(2),
            result.balance_payment_term,
            (result.interest_rate * 100).toFixed(2) + '%',
            result.loanable_amount.toFixed(2),
            result.required_equity.toFixed(2),
            result.cash_out.toFixed(2),
            calculateTotalInterest(result).toFixed(2),
        ];
    });

    const csv = [
        headers.join(','),
        ...rows.map(r => r.join(',')),
    ].join('\n');

    const blob = new Blob([csv], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'mortgage-comparison.csv';
    a.click();
    URL.revokeObjectURL(url);
};
</script>

<template>
    <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-lg max-w-7xl w-full max-h-[90vh] overflow-hidden flex flex-col">
            <!-- Header -->
            <div class="border-b border-gray-200 px-6 py-4 flex justify-between items-center">
                <div>
                    <h2 class="text-xl font-semibold text-gray-900">Lending Institution Comparison</h2>
                    <p class="text-sm text-gray-600 mt-1">Compare mortgages across all institutions</p>
                </div>
                <div class="flex gap-2">
                    <button
                        @click="exportToCSV"
                        class="px-4 py-2 text-sm bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200"
                    >
                        Export CSV
                    </button>
                    <button
                        @click="emit('close')"
                        class="px-4 py-2 text-sm border border-gray-300 rounded-md hover:bg-gray-50"
                    >
                        Close
                    </button>
                </div>
            </div>

            <!-- Best Options Summary -->
            <div class="px-6 py-4 bg-blue-50 border-b border-blue-100">
                <h3 class="text-sm font-semibold text-blue-900 mb-2">Best Options</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
                    <div v-if="bestOptions.lowestMonthlyPayment" class="flex items-center gap-2">
                        <span class="text-blue-700">💰 Lowest Monthly:</span>
                        <span class="font-semibold">{{ bestOptions.lowestMonthlyPayment.institution.toUpperCase() }}</span>
                        <span class="text-blue-600">{{ formatCurrency(bestOptions.lowestMonthlyPayment.value) }}</span>
                    </div>
                    <div v-if="bestOptions.lowestTotalInterest" class="flex items-center gap-2">
                        <span class="text-blue-700">📉 Lowest Interest:</span>
                        <span class="font-semibold">{{ bestOptions.lowestTotalInterest.institution.toUpperCase() }}</span>
                        <span class="text-blue-600">{{ formatCurrency(bestOptions.lowestTotalInterest.value) }}</span>
                    </div>
                    <div v-if="bestOptions.lowestCashOut" class="flex items-center gap-2">
                        <span class="text-blue-700">💵 Lowest Cash Out:</span>
                        <span class="font-semibold">{{ bestOptions.lowestCashOut.institution.toUpperCase() }}</span>
                        <span class="text-blue-600">{{ formatCurrency(bestOptions.lowestCashOut.value) }}</span>
                    </div>
                </div>
            </div>

            <!-- Comparison Table -->
            <div class="flex-1 overflow-auto px-6 py-4">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50 sticky top-0">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Institution</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Qualifies</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Monthly Payment</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Term</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Interest Rate</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Loanable Amount</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Required Equity</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Cash Out</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total Interest</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="comp in comparisons" :key="comp.institution" class="hover:bg-gray-50">
                            <!-- Institution Name -->
                            <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                {{ comp.institutionName }}
                            </td>

                            <!-- Error State -->
                            <template v-if="comp.error">
                                <td colspan="8" class="px-4 py-3 text-sm text-red-600 text-center">
                                    {{ comp.error }}
                                </td>
                            </template>

                            <!-- Success State -->
                            <template v-else>
                                <!-- Qualifies -->
                                <td class="px-4 py-3 text-sm text-center">
                                    <span v-if="comp.result.qualification.qualifies" class="text-green-600 font-semibold">✓ Yes</span>
                                    <span v-else class="text-red-600 font-semibold">✗ No</span>
                                </td>

                                <!-- Monthly Payment -->
                                <td class="px-4 py-3 text-sm text-right">
                                    <span :class="{'font-bold text-blue-600': isBest(comp.institution, 'lowestMonthlyPayment')}">
                                        {{ formatCurrency(comp.result.monthly_amortization) }}
                                    </span>
                                    <span v-if="isBest(comp.institution, 'lowestMonthlyPayment')" class="ml-1">🏆</span>
                                </td>

                                <!-- Term -->
                                <td class="px-4 py-3 text-sm text-right text-gray-900">
                                    {{ comp.result.balance_payment_term }} yrs
                                </td>

                                <!-- Interest Rate -->
                                <td class="px-4 py-3 text-sm text-right text-gray-900">
                                    {{ formatPercent(comp.result.interest_rate) }}
                                </td>

                                <!-- Loanable Amount -->
                                <td class="px-4 py-3 text-sm text-right text-gray-900">
                                    {{ formatCurrency(comp.result.loanable_amount) }}
                                </td>

                                <!-- Required Equity -->
                                <td class="px-4 py-3 text-sm text-right text-gray-900">
                                    {{ formatCurrency(comp.result.required_equity) }}
                                </td>

                                <!-- Cash Out -->
                                <td class="px-4 py-3 text-sm text-right">
                                    <span :class="{'font-bold text-blue-600': isBest(comp.institution, 'lowestCashOut')}">
                                        {{ formatCurrency(comp.result.cash_out) }}
                                    </span>
                                    <span v-if="isBest(comp.institution, 'lowestCashOut')" class="ml-1">🏆</span>
                                </td>

                                <!-- Total Interest -->
                                <td class="px-4 py-3 text-sm text-right">
                                    <span :class="{'font-bold text-blue-600': isBest(comp.institution, 'lowestTotalInterest')}">
                                        {{ formatCurrency(calculateTotalInterest(comp.result)) }}
                                    </span>
                                    <span v-if="isBest(comp.institution, 'lowestTotalInterest')" class="ml-1">🏆</span>
                                </td>
                            </template>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Footer -->
            <div class="border-t border-gray-200 px-6 py-4 bg-gray-50">
                <p class="text-xs text-gray-600">
                    💡 Best options are marked with 🏆. Results may vary based on your specific financial situation.
                </p>
            </div>
        </div>
    </div>
</template>
