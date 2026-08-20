<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import categoryRoutes from '@/routes/categories';
import type { Category } from '@/types';

defineProps<{ category: Category }>();
</script>

<template>
    <Head :title="category.name" />
    <div class="flex max-w-2xl flex-col gap-6">
        <Heading :title="category.name" description="Category details." />
        <div class="grid gap-4 rounded-xl border p-6">
            <div class="flex items-center justify-between">
                <span class="text-muted-foreground">Classification</span
                ><Badge>{{ category.type }}</Badge>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-muted-foreground">COA entries</span
                ><span>{{ category.chart_of_accounts_count ?? 0 }}</span>
            </div>
        </div>
        <div class="flex gap-2">
            <Button as-child
                ><Link :href="categoryRoutes.edit.url(category.id)"
                    >Edit</Link
                ></Button
            >
            <Button as-child variant="outline"
                ><Link :href="categoryRoutes.index()">Back</Link></Button
            >
        </div>
    </div>
</template>
