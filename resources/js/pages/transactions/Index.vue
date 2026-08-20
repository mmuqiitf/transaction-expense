<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { h, ref } from 'vue';
import TransactionController from '@/actions/App/Http/Controllers/TransactionController';
import ConfirmDeleteButton from '@/components/ConfirmDeleteButton.vue';
import {
    DataTable,
    DataTablePagination,
    DataTableToolbar,
} from '@/components/data-table';
import type { DataTableColumnDef } from '@/components/data-table';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useTableQuery } from '@/composables/useTableQuery';
import { formatCurrency, formatDate } from '@/lib/format';
import transactionRoutes from '@/routes/transactions';
import type {
    Category,
    CategoryType,
    ChartOfAccount,
    Paginated,
    SelectOption,
    Transaction,
} from '@/types';

const props = defineProps<{
    transactions: Paginated<Transaction>;
    categories: { data: Category[] };
    accounts: { data: ChartOfAccount[] };
    categoryTypes: SelectOption<CategoryType>[];
    filters: {
        search?: string | null;
        from?: string | null;
        to?: string | null;
        type?: CategoryType | null;
        category_id?: number | null;
        chart_of_account_id?: number | null;
        sort?: string | null;
        direction?: 'asc' | 'desc';
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Transactions', href: transactionRoutes.index() },
        ],
    },
});

const search = ref(props.filters.search ?? '');
const tableQuery = useTableQuery(transactionRoutes.index.url);

function query(overrides: Record<string, unknown> = {}) {
    return {
        search: search.value || undefined,
        sort: props.filters.sort ?? undefined,
        direction: props.filters.direction ?? undefined,
        filter: {
            from: props.filters.from ?? undefined,
            to: props.filters.to ?? undefined,
            type: props.filters.type ?? undefined,
            category_id: props.filters.category_id ?? undefined,
            chart_of_account_id: props.filters.chart_of_account_id ?? undefined,
        },
        ...overrides,
    };
}

const updateSearch = useDebounceFn((value: string) => {
    search.value = value;
    tableQuery.update(query());
}, 300);

function updateDateFilter(key: 'from' | 'to', value: string): void {
    tableQuery.update({
        ...query(),
        filter: { ...query().filter, [key]: value || undefined },
    });
}

function updateFilter(
    key: 'type' | 'category_id' | 'chart_of_account_id',
    value: string,
): void {
    tableQuery.update({
        ...query(),
        filter: {
            ...query().filter,
            [key]:
                value === 'all'
                    ? undefined
                    : key === 'type'
                      ? value
                      : Number(value),
        },
    });
}

function resetFilters(): void {
    search.value = '';
    tableQuery.update({});
}

const columns: DataTableColumnDef<Transaction>[] = [
    {
        accessorKey: 'transaction_date',
        header: 'Date',
        enableSorting: true,
        cell: ({ row }) => formatDate(row.original.transaction_date),
    },
    {
        id: 'account',
        header: 'Account',
        cell: ({ row }) =>
            h('div', { class: 'flex flex-col' }, [
                h(
                    'span',
                    { class: 'font-medium' },
                    row.original.chart_of_account.name,
                ),
                h(
                    'span',
                    { class: 'text-xs text-muted-foreground' },
                    row.original.chart_of_account.code,
                ),
            ]),
    },
    {
        id: 'category',
        header: 'Category',
        cell: ({ row }) => row.original.chart_of_account.category.name,
    },
    {
        id: 'type',
        header: 'Classification',
        cell: ({ row }) =>
            h(
                Badge,
                {
                    variant:
                        row.original.chart_of_account.category.type === 'income'
                            ? 'default'
                            : 'secondary',
                },
                () => row.original.chart_of_account.category.type,
            ),
    },
    {
        accessorKey: 'description',
        header: 'Description',
        cell: ({ row }) => row.original.description ?? '—',
    },
    {
        accessorKey: 'amount',
        header: 'Amount',
        enableSorting: true,
        cell: ({ row }) =>
            h(
                'span',
                {
                    class:
                        row.original.chart_of_account.category.type === 'income'
                            ? 'font-medium text-emerald-600'
                            : 'font-medium text-destructive',
                },
                `${row.original.chart_of_account.category.type === 'income' ? '+' : '-'} ${formatCurrency(row.original.amount)}`,
            ),
    },
    {
        id: 'actions',
        header: '',
        cell: ({ row }) =>
            h('div', { class: 'flex justify-end gap-1' }, [
                h(
                    Link,
                    {
                        href: transactionRoutes.edit.url(row.original.id),
                        class: 'inline-flex items-center rounded-md px-2 py-1 text-sm hover:bg-muted',
                    },
                    () => 'Edit',
                ),
                h(ConfirmDeleteButton, {
                    url: TransactionController.destroy.url(row.original.id),
                }),
            ]),
    },
];
</script>

<template>
    <Head title="Transactions" />
    <div class="flex flex-col gap-6">
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"
        >
            <Heading
                title="Transactions"
                description="Record income and expenses in the shared ledger."
            /><Button as-child
                ><Link :href="transactionRoutes.create()"
                    >Add transaction</Link
                ></Button
            >
        </div>
        <DataTableToolbar
            :search="search"
            placeholder="Search description, account, or category..."
            :has-active-filters="
                Boolean(
                    search ||
                    filters.from ||
                    filters.to ||
                    filters.type ||
                    filters.category_id ||
                    filters.chart_of_account_id,
                )
            "
            @update:search="updateSearch"
            @reset="resetFilters"
        >
            <template #filters>
                <Input
                    type="date"
                    :model-value="filters.from ?? ''"
                    class="w-[150px]"
                    aria-label="From date"
                    @update:model-value="
                        updateDateFilter('from', String($event))
                    "
                />
                <Input
                    type="date"
                    :model-value="filters.to ?? ''"
                    class="w-[150px]"
                    aria-label="To date"
                    @update:model-value="updateDateFilter('to', String($event))"
                />
                <Select
                    :model-value="filters.type ?? 'all'"
                    @update:model-value="updateFilter('type', String($event))"
                    ><SelectTrigger class="w-[150px]"
                        ><SelectValue placeholder="Type" /></SelectTrigger
                    ><SelectContent
                        ><SelectItem value="all">All types</SelectItem
                        ><SelectItem
                            v-for="option in categoryTypes"
                            :key="option.value"
                            :value="option.value"
                            >{{ option.label }}</SelectItem
                        ></SelectContent
                    ></Select
                >
                <Select
                    :model-value="
                        filters.category_id
                            ? String(filters.category_id)
                            : 'all'
                    "
                    @update:model-value="
                        updateFilter('category_id', String($event))
                    "
                    ><SelectTrigger class="w-[170px]"
                        ><SelectValue placeholder="Category" /></SelectTrigger
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
                <Select
                    :model-value="
                        filters.chart_of_account_id
                            ? String(filters.chart_of_account_id)
                            : 'all'
                    "
                    @update:model-value="
                        updateFilter('chart_of_account_id', String($event))
                    "
                    ><SelectTrigger class="w-[170px]"
                        ><SelectValue placeholder="Account" /></SelectTrigger
                    ><SelectContent
                        ><SelectItem value="all">All accounts</SelectItem
                        ><SelectItem
                            v-for="account in accounts.data"
                            :key="account.id"
                            :value="String(account.id)"
                            >{{ account.code }} — {{ account.name }}</SelectItem
                        ></SelectContent
                    ></Select
                >
            </template>
        </DataTableToolbar>
        <div class="rounded-xl border">
            <DataTable
                :columns="columns"
                :data="transactions.data"
                :sort="filters.sort"
                :direction="filters.direction"
                @sort="
                    (sort, direction) =>
                        tableQuery.update(query({ sort, direction }))
                "
            />
        </div>
        <DataTablePagination
            :meta="transactions.meta"
            @page="(page) => tableQuery.visit(query({ page }))"
            @per-page="
                (perPage) => tableQuery.update(query({ per_page: perPage }))
            "
        />
    </div>
</template>
