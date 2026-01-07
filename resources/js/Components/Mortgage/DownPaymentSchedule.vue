<script setup>
import { computed } from 'vue';

const props = defineProps({
    schedule: {
        type: Array,
        required: true,
    },
    totalAmount: {
        type: Number,
        required: true,
    },
});

const formatCurrency = (value) => {
    if (!value && value !== 0) return '₱0.00';
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
    }).format(value);
};

const totalPayments = computed(() => props.schedule.length);
</script>

<template>
    <div class="bg-white shadow rounded-lg">
        <!-- Header -->
        <div class="border-b border-gray-200 px-6 py-4">
            <h3 class="text-lg font-semibold text-gray-900">Down Payment Schedule</h3>
            <p class="text-sm text-gray-600 mt-1">No interest charged on down payment installments</p>
        </div>

        <!-- Content -->
        <div class="p-6">
            <!-- Summary Card -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div class="bg-blue-50 p-4 rounded-lg">
                    <p class="text-sm text-gray-600">Total Down Payment</p>
                    <p class="text-xl font-bold text-gray-900">{{ formatCurrency(totalAmount) }}</p>
                </div>
                <div class="bg-green-50 p-4 rounded-lg">
                    <p class="text-sm text-gray-600">Number of Payments</p>
                    <p class="text-xl font-bold text-gray-900">{{ totalPayments }}</p>
                </div>
            </div>

            <!-- Schedule Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Month</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Payment Amount</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="payment in schedule" :key="payment.month" class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                {{ payment.label }}
                            </td>
                            <td class="px-4 py-3 text-sm text-right font-semibold text-blue-600">
                                {{ formatCurrency(payment.amount) }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">
                                <template v-if="payment.month === 0">
                                    Full payment due immediately
                                </template>
                                <template v-else>
                                    Due before loan starts
                                </template>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Note -->
            <div class="mt-4 p-3 bg-yellow-50 border-l-4 border-yellow-400 rounded">
                <p class="text-sm text-yellow-800">
                    <strong>Note:</strong> The down payment schedule must be completed before your monthly amortization begins.
                    These payments are interest-free.
                </p>
            </div>
        </div>
    </div>
</template>
