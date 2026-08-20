<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatCurrency, formatDate } from '@/lib/format';
import transactionRoutes from '@/routes/transactions';
import type { Transaction } from '@/types';

defineProps<{ transaction: Transaction }>();
</script>

<template>
    <Head title="Transaction details" />
    <div class="flex max-w-2xl flex-col gap-6">
        <Heading
            title="Transaction details"
            description="Review the financial entry."
        />
        <div class="grid gap-4 rounded-xl border p-6">
            <div class="flex items-center justify-between">
                <span class="text-muted-foreground">Date</span
                ><span>{{ formatDate(transaction.transaction_date) }}</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-muted-foreground">Account</span
                ><span
                    >{{ transaction.chart_of_account.code }} —
                    {{ transaction.chart_of_account.name }}</span
                >
            </div>
            <div class="flex items-center justify-between">
                <span class="text-muted-foreground">Classification</span
                ><Badge>{{ transaction.chart_of_account.category.type }}</Badge>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-muted-foreground">Amount</span
                ><span>{{ formatCurrency(transaction.amount) }}</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-muted-foreground">Description</span
                ><span>{{ transaction.description ?? '—' }}</span>
            </div>
        </div>
        <div class="flex gap-2">
            <Button as-child
                ><Link :href="transactionRoutes.edit.url(transaction.id)"
                    >Edit</Link
                ></Button
            ><Button as-child variant="outline"
                ><Link :href="transactionRoutes.index()">Back</Link></Button
            >
        </div>
    </div>
</template>
