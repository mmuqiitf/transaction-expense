<script setup lang="ts">
import { Head, Form, Link } from '@inertiajs/vue3';
import TransactionController from '@/actions/App/Http/Controllers/TransactionController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import transactionRoutes from '@/routes/transactions';
import type { ChartOfAccount, Transaction } from '@/types';

const props = defineProps<{
    transaction: Transaction;
    accounts: { data: ChartOfAccount[] };
}>();
defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Transactions', href: transactionRoutes.index() },
            { title: 'Edit', href: '#' },
        ],
    },
});
</script>

<template>
    <Head title="Edit transaction" />
    <div class="flex max-w-2xl flex-col gap-6">
        <Heading
            title="Edit transaction"
            description="Update the financial entry."
        /><Form
            v-bind="TransactionController.update.form(props.transaction.id)"
            class="grid gap-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="transaction_date">Date</Label
                ><Input
                    id="transaction_date"
                    name="transaction_date"
                    type="date"
                    required
                    :default-value="transaction.transaction_date"
                /><InputError :message="errors.transaction_date" />
            </div>
            <div class="grid gap-2">
                <Label for="chart_of_account_id">Account</Label
                ><Select
                    name="chart_of_account_id"
                    required
                    :default-value="String(transaction.chart_of_account.id)"
                    ><SelectTrigger id="chart_of_account_id"
                        ><SelectValue /></SelectTrigger
                    ><SelectContent
                        ><SelectItem
                            v-for="account in accounts.data"
                            :key="account.id"
                            :value="String(account.id)"
                            >{{ account.code }} — {{ account.name }} ({{
                                account.category.name
                            }})</SelectItem
                        ></SelectContent
                    ></Select
                ><InputError :message="errors.chart_of_account_id" />
            </div>
            <div class="grid gap-2">
                <Label for="amount">Amount (IDR)</Label
                ><Input
                    id="amount"
                    name="amount"
                    type="number"
                    min="0"
                    step="1"
                    required
                    :default-value="transaction.amount"
                /><InputError :message="errors.amount" />
            </div>
            <div class="grid gap-2">
                <Label for="description">Description</Label
                ><Input
                    id="description"
                    name="description"
                    :default-value="transaction.description ?? ''"
                /><InputError :message="errors.description" />
            </div>
            <div class="flex gap-2">
                <Button :disabled="processing">Save changes</Button
                ><Button as-child variant="outline"
                    ><Link :href="transactionRoutes.index()"
                        >Cancel</Link
                    ></Button
                >
            </div>
        </Form>
    </div>
</template>
