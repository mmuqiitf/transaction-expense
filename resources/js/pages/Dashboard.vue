<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { formatCurrency, formatDate } from '@/lib/format';
import { dashboard } from '@/routes';
import transactionRoutes from '@/routes/transactions';
import type { Transaction } from '@/types';

defineProps<{
    summary: {
        year: number;
        income: number;
        expense: number;
        net_income: number;
    };
    latestTransactions: { data: Transaction[] };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex flex-col gap-6">
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"
        >
            <Heading
                title="Dashboard"
                :description="`Shared ledger overview for ${summary.year}.`"
            />
            <Button as-child
                ><Link :href="transactionRoutes.create()"
                    >Add transaction</Link
                ></Button
            >
        </div>
        <div class="grid gap-4 md:grid-cols-3">
            <div class="rounded-xl border p-5">
                <p class="text-sm text-muted-foreground">Income</p>
                <p class="mt-2 text-2xl font-semibold text-emerald-600">
                    {{ formatCurrency(summary.income) }}
                </p>
            </div>
            <div class="rounded-xl border p-5">
                <p class="text-sm text-muted-foreground">Expenses</p>
                <p class="mt-2 text-2xl font-semibold text-destructive">
                    {{ formatCurrency(summary.expense) }}
                </p>
            </div>
            <div class="rounded-xl border p-5">
                <p class="text-sm text-muted-foreground">Net income</p>
                <p class="mt-2 text-2xl font-semibold">
                    {{ formatCurrency(summary.net_income) }}
                </p>
            </div>
        </div>
        <div class="rounded-xl border p-5">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="font-semibold">Recent transactions</h2>
                <Button as-child variant="outline" size="sm"
                    ><Link :href="transactionRoutes.index()"
                        >View all</Link
                    ></Button
                >
            </div>
            <div v-if="latestTransactions.data.length" class="divide-y">
                <div
                    v-for="transaction in latestTransactions.data"
                    :key="transaction.id"
                    class="flex flex-col gap-1 py-3 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div>
                        <p class="font-medium">
                            {{ transaction.chart_of_account.name }}
                        </p>
                        <p class="text-sm text-muted-foreground">
                            {{ formatDate(transaction.transaction_date) }} ·
                            {{ transaction.description ?? 'No description' }}
                        </p>
                    </div>
                    <p
                        :class="
                            transaction.chart_of_account.category.type ===
                            'income'
                                ? 'font-medium text-emerald-600'
                                : 'font-medium text-destructive'
                        "
                    >
                        {{
                            transaction.chart_of_account.category.type ===
                            'income'
                                ? '+'
                                : '-'
                        }}
                        {{ formatCurrency(transaction.amount) }}
                    </p>
                </div>
            </div>
            <p v-else class="py-8 text-center text-sm text-muted-foreground">
                No transactions yet.
            </p>
        </div>
    </div>
</template>
