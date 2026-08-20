<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import accountRoutes from '@/routes/chart-of-accounts';
import type { ChartOfAccount } from '@/types';

defineProps<{ account: ChartOfAccount }>();
</script>

<template>
    <Head :title="account.name" />
    <div class="flex max-w-2xl flex-col gap-6">
        <Heading
            :title="account.name"
            description="Chart-of-account details."
        />
        <div class="grid gap-4 rounded-xl border p-6">
            <div class="flex items-center justify-between">
                <span class="text-muted-foreground">Code</span
                ><span>{{ account.code }}</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-muted-foreground">Category</span
                ><span>{{ account.category.name }}</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-muted-foreground">Classification</span
                ><Badge>{{ account.category.type }}</Badge>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-muted-foreground">Transactions</span
                ><span>{{ account.transactions_count ?? 0 }}</span>
            </div>
        </div>
        <div class="flex gap-2">
            <Button as-child
                ><Link :href="accountRoutes.edit.url(account.id)"
                    >Edit</Link
                ></Button
            ><Button as-child variant="outline"
                ><Link :href="accountRoutes.index()">Back</Link></Button
            >
        </div>
    </div>
</template>
