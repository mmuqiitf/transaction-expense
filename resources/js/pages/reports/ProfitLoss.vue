<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import ProfitLossController from '@/actions/App/Http/Controllers/Reports/ProfitLossController';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatCurrency } from '@/lib/format';
import reportRoutes from '@/routes/reports';
import type { Category, ProfitLossReport } from '@/types';

const props = defineProps<{
    report: ProfitLossReport;
    categories: { data: Category[] };
    years: number[];
    filters: {
        from: string;
        to: string;
        year: number | null;
        category_id: number | null;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Profit/Loss Report',
                href: reportRoutes.profitLoss.index(),
            },
        ],
    },
});

const from = ref(props.filters.from);
const to = ref(props.filters.to);
const year = ref(props.filters.year ? String(props.filters.year) : 'custom');
const categoryId = ref(
    props.filters.category_id ? String(props.filters.category_id) : 'all',
);
const availableYears = computed(() => [...props.years].sort((a, b) => b - a));

function reportFilter(): Record<string, number | string | undefined> {
    if (year.value !== 'custom') {
        return { year: Number(year.value) };
    }

    return {
        from: from.value || undefined,
        to: to.value || undefined,
    };
}

const exportUrl = computed(() =>
    ProfitLossController.exportMethod.url({
        query: {
            filter: {
                ...reportFilter(),
                category_id:
                    categoryId.value === 'all'
                        ? undefined
                        : Number(categoryId.value),
            },
        },
    }),
);

function applyFilters(): void {
    router.get(
        reportRoutes.profitLoss.index.url({
            query: {
                filter: {
                    ...reportFilter(),
                    category_id:
                        categoryId.value === 'all'
                            ? undefined
                            : Number(categoryId.value),
                },
            },
        }),
        {},
        { preserveState: true, replace: true },
    );
}

function updateYear(value: string): void {
    year.value = value;

    if (value !== 'custom') {
        from.value = '';
        to.value = '';
    }
}

function totalFor(type: 'income' | 'expense'): number {
    return Object.values(props.report.totals[type]).reduce(
        (total, amount) => total + amount,
        0,
    );
}

const netTotal = computed(() =>
    Object.values(props.report.net_income).reduce(
        (total, amount) => total + amount,
        0,
    ),
);
</script>

<template>
    <Head title="Profit/Loss Report" />
    <div class="flex flex-col gap-6">
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"
        >
            <Heading
                title="Profit/Loss Report"
                description="Review income, expenses, and net income by month."
            />
            <Button as-child variant="outline"
                ><a :href="exportUrl">Export XLSX</a></Button
            >
        </div>
        <div
            class="flex flex-col gap-3 rounded-xl border p-4 sm:flex-row sm:items-end"
        >
            <div class="grid flex-1 gap-2">
                <label for="year" class="text-sm font-medium">Year</label>
                <Select
                    :model-value="year"
                    @update:model-value="updateYear(String($event))"
                >
                    <SelectTrigger id="year">
                        <SelectValue placeholder="Select year" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="option in availableYears"
                            :key="option"
                            :value="String(option)"
                        >
                            {{ option }}
                        </SelectItem>
                        <SelectItem value="custom">Custom range</SelectItem>
                    </SelectContent>
                </Select>
            </div>
            <div v-if="year === 'custom'" class="grid flex-1 gap-2">
                <label for="from" class="text-sm font-medium">From</label
                ><Input id="from" v-model="from" type="date" />
            </div>
            <div v-if="year === 'custom'" class="grid flex-1 gap-2">
                <label for="to" class="text-sm font-medium">To</label
                ><Input id="to" v-model="to" type="date" />
            </div>
            <div class="grid flex-1 gap-2">
                <label for="category" class="text-sm font-medium"
                    >Category</label
                ><Select v-model="categoryId"
                    ><SelectTrigger id="category"
                        ><SelectValue
                            placeholder="All categories" /></SelectTrigger
                    ><SelectContent
                        ><SelectItem value="all">All categories</SelectItem
                        ><SelectItem
                            v-for="category in categories.data"
                            :key="category.id"
                            :value="String(category.id)"
                            >{{ category.name }}</SelectItem
                        ></SelectContent
                    ></Select
                >
            </div>
            <Button type="button" @click="applyFilters">Apply filters</Button>
        </div>
        <div class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-xl border p-4">
                <p class="text-sm text-muted-foreground">Total income</p>
                <p class="mt-1 text-xl font-semibold text-emerald-600">
                    {{ formatCurrency(totalFor('income')) }}
                </p>
            </div>
            <div class="rounded-xl border p-4">
                <p class="text-sm text-muted-foreground">Total expense</p>
                <p class="mt-1 text-xl font-semibold text-destructive">
                    {{ formatCurrency(totalFor('expense')) }}
                </p>
            </div>
            <div class="rounded-xl border p-4">
                <p class="text-sm text-muted-foreground">Net income</p>
                <p class="mt-1 text-xl font-semibold">
                    {{ formatCurrency(netTotal) }}
                </p>
            </div>
        </div>
        <div class="rounded-xl border">
            <Table
                ><TableHeader
                    ><TableRow
                        ><TableHead class="sticky left-0 bg-background"
                            >Category</TableHead
                        ><TableHead>Classification</TableHead
                        ><TableHead
                            v-for="month in report.months"
                            :key="month.key"
                            class="text-right"
                            >{{ month.label }}</TableHead
                        ><TableHead class="text-right"
                            >Total</TableHead
                        ></TableRow
                    ></TableHeader
                ><TableBody>
                    <TableRow v-for="row in report.rows" :key="row.id"
                        ><TableCell
                            class="sticky left-0 bg-background font-medium"
                            >{{ row.name }}</TableCell
                        ><TableCell class="capitalize">{{ row.type }}</TableCell
                        ><TableCell
                            v-for="month in report.months"
                            :key="month.key"
                            class="text-right"
                            >{{
                                formatCurrency(row.amounts[month.key])
                            }}</TableCell
                        ><TableCell class="text-right font-medium">{{
                            formatCurrency(row.total)
                        }}</TableCell></TableRow
                    >
                    <TableRow class="bg-muted/50 font-semibold"
                        ><TableCell class="sticky left-0 bg-muted/50"
                            >Total Income</TableCell
                        ><TableCell>income</TableCell
                        ><TableCell
                            v-for="month in report.months"
                            :key="month.key"
                            class="text-right"
                            >{{
                                formatCurrency(report.totals.income[month.key])
                            }}</TableCell
                        ><TableCell class="text-right">{{
                            formatCurrency(totalFor('income'))
                        }}</TableCell></TableRow
                    >
                    <TableRow class="bg-muted/50 font-semibold"
                        ><TableCell class="sticky left-0 bg-muted/50"
                            >Total Expense</TableCell
                        ><TableCell>expense</TableCell
                        ><TableCell
                            v-for="month in report.months"
                            :key="month.key"
                            class="text-right"
                            >{{
                                formatCurrency(report.totals.expense[month.key])
                            }}</TableCell
                        ><TableCell class="text-right">{{
                            formatCurrency(totalFor('expense'))
                        }}</TableCell></TableRow
                    >
                    <TableRow class="font-bold"
                        ><TableCell class="sticky left-0 bg-background"
                            >Net Income</TableCell
                        ><TableCell>net</TableCell
                        ><TableCell
                            v-for="month in report.months"
                            :key="month.key"
                            class="text-right"
                            >{{
                                formatCurrency(report.net_income[month.key])
                            }}</TableCell
                        ><TableCell class="text-right">{{
                            formatCurrency(netTotal)
                        }}</TableCell></TableRow
                    >
                </TableBody></Table
            >
        </div>
    </div>
</template>
