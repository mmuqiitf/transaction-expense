<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Filter, X } from '@lucide/vue';
import { useDebounceFn } from '@vueuse/core';
import { computed, h, ref } from 'vue';
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
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetFooter,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
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

type TransactionFilterKey =
    'search' | 'from' | 'to' | 'type' | 'category_id' | 'chart_of_account_id';

type TransactionFilterDraft = {
    from: string;
    to: string;
    type: CategoryType | 'all';
    category_id: string;
    chart_of_account_id: string;
};

type ActiveFilter = {
    key: TransactionFilterKey;
    label: string;
    value: string;
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Transactions', href: transactionRoutes.index() },
        ],
    },
});

const search = ref(props.filters.search ?? '');
const filterDrawerOpen = ref(false);
const filterDraft = ref<TransactionFilterDraft>(emptyFilterDraft());
const tableQuery = useTableQuery(transactionRoutes.index.url);

function emptyFilterDraft(): TransactionFilterDraft {
    return {
        from: '',
        to: '',
        type: 'all',
        category_id: 'all',
        chart_of_account_id: 'all',
    };
}

function currentFilterQuery(): Record<string, string | number | undefined> {
    return {
        from: props.filters.from ?? undefined,
        to: props.filters.to ?? undefined,
        type: props.filters.type ?? undefined,
        category_id: props.filters.category_id ?? undefined,
        chart_of_account_id: props.filters.chart_of_account_id ?? undefined,
    };
}

function query(overrides: Record<string, unknown> = {}) {
    return {
        search: search.value || undefined,
        sort: props.filters.sort ?? undefined,
        direction: props.filters.direction ?? undefined,
        filter: currentFilterQuery(),
        ...overrides,
    };
}

const updateSearch = useDebounceFn((value: string) => {
    search.value = value;
    tableQuery.update(query());
}, 300);

function openFilterDrawer(): void {
    filterDraft.value = {
        from: props.filters.from ?? '',
        to: props.filters.to ?? '',
        type: props.filters.type ?? 'all',
        category_id: props.filters.category_id
            ? String(props.filters.category_id)
            : 'all',
        chart_of_account_id: props.filters.chart_of_account_id
            ? String(props.filters.chart_of_account_id)
            : 'all',
    };
    filterDrawerOpen.value = true;
}

function applyFilters(): void {
    tableQuery.update({
        ...query(),
        filter: {
            from: filterDraft.value.from || undefined,
            to: filterDraft.value.to || undefined,
            type:
                filterDraft.value.type === 'all'
                    ? undefined
                    : filterDraft.value.type,
            category_id:
                filterDraft.value.category_id === 'all'
                    ? undefined
                    : Number(filterDraft.value.category_id),
            chart_of_account_id:
                filterDraft.value.chart_of_account_id === 'all'
                    ? undefined
                    : Number(filterDraft.value.chart_of_account_id),
        },
    });
    filterDrawerOpen.value = false;
}

function resetFilters(): void {
    search.value = '';
    tableQuery.update({});
}

function removeFilter(key: TransactionFilterKey): void {
    if (key === 'search') {
        search.value = '';
        tableQuery.update(query());

        return;
    }

    tableQuery.update({
        ...query(),
        filter: {
            ...currentFilterQuery(),
            [key]: undefined,
        },
    });
}

function categoryName(categoryId: number): string {
    return (
        props.categories.data.find((category) => category.id === categoryId)
            ?.name ?? `Category #${categoryId}`
    );
}

function accountName(accountId: number): string {
    const account = props.accounts.data.find(
        (account) => account.id === accountId,
    );

    return account
        ? `${account.code} — ${account.name}`
        : `Account #${accountId}`;
}

const activeFilters = computed<ActiveFilter[]>(() => {
    const active: ActiveFilter[] = [];

    if (search.value) {
        active.push({ key: 'search', label: 'Search', value: search.value });
    }

    if (props.filters.from) {
        active.push({
            key: 'from',
            label: 'From',
            value: formatDate(props.filters.from),
        });
    }

    if (props.filters.to) {
        active.push({
            key: 'to',
            label: 'To',
            value: formatDate(props.filters.to),
        });
    }

    if (props.filters.type) {
        active.push({
            key: 'type',
            label: 'Type',
            value:
                props.categoryTypes.find(
                    (option) => option.value === props.filters.type,
                )?.label ?? props.filters.type,
        });
    }

    if (props.filters.category_id) {
        active.push({
            key: 'category_id',
            label: 'Category',
            value: categoryName(props.filters.category_id),
        });
    }

    if (props.filters.chart_of_account_id) {
        active.push({
            key: 'chart_of_account_id',
            label: 'Account',
            value: accountName(props.filters.chart_of_account_id),
        });
    }

    return active;
});

const appliedFilterCount = computed(
    () =>
        activeFilters.value.filter((filter) => filter.key !== 'search').length,
);

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
            @update:search="updateSearch"
        >
            <template #actions>
                <Button
                    type="button"
                    variant="outline"
                    class="w-full sm:w-auto"
                    @click="openFilterDrawer"
                >
                    <Filter class="size-4" />
                    Filters
                    <span
                        v-if="appliedFilterCount"
                        class="ml-1 rounded-full bg-primary px-1.5 py-0.5 text-xs text-primary-foreground"
                    >
                        {{ appliedFilterCount }}
                    </span>
                </Button>
            </template>
        </DataTableToolbar>
        <div
            v-if="activeFilters.length"
            class="flex flex-wrap items-center gap-2 rounded-lg border bg-muted/30 px-3 py-2"
        >
            <span class="mr-1 text-sm text-muted-foreground">Filtered by</span>
            <div
                v-for="activeFilter in activeFilters"
                :key="activeFilter.key"
                class="inline-flex max-w-full items-center gap-1 rounded-md border bg-background px-2 py-1 text-sm"
            >
                <span class="text-muted-foreground"
                    >{{ activeFilter.label }}:</span
                >
                <span class="max-w-48 truncate">{{ activeFilter.value }}</span>
                <button
                    type="button"
                    class="ml-1 rounded-sm p-0.5 text-muted-foreground hover:bg-muted hover:text-foreground"
                    :aria-label="`Remove ${activeFilter.label} filter`"
                    @click="removeFilter(activeFilter.key)"
                >
                    <span class="sr-only"
                        >Remove {{ activeFilter.label }} filter</span
                    >
                    <X class="size-3" />
                </button>
            </div>
            <Button
                type="button"
                variant="ghost"
                size="sm"
                class="ml-auto"
                @click="resetFilters"
            >
                Clear all
            </Button>
        </div>
        <Sheet v-model:open="filterDrawerOpen">
            <SheetContent
                class="w-full overflow-y-auto p-0 sm:max-w-md"
                @open-auto-focus="(event) => event.preventDefault()"
            >
                <SheetHeader class="border-b px-6 py-5">
                    <SheetTitle>Filter transactions</SheetTitle>
                    <SheetDescription>
                        Narrow the ledger by date, classification, category, or
                        account.
                    </SheetDescription>
                </SheetHeader>
                <div class="flex flex-col gap-6 px-6 py-6">
                    <div class="flex flex-col gap-2">
                        <label
                            for="transaction-filter-from"
                            class="text-sm font-medium"
                            >From date</label
                        >
                        <Input
                            id="transaction-filter-from"
                            v-model="filterDraft.from"
                            type="date"
                        />
                    </div>
                    <div class="flex flex-col gap-2">
                        <label
                            for="transaction-filter-to"
                            class="text-sm font-medium"
                            >To date</label
                        >
                        <Input
                            id="transaction-filter-to"
                            v-model="filterDraft.to"
                            type="date"
                        />
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="text-sm font-medium">Type</label>
                        <Select v-model="filterDraft.type">
                            <SelectTrigger>
                                <SelectValue placeholder="All types" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All types</SelectItem>
                                <SelectItem
                                    v-for="option in categoryTypes"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="text-sm font-medium">Category</label>
                        <Select v-model="filterDraft.category_id">
                            <SelectTrigger>
                                <SelectValue placeholder="All categories" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all"
                                    >All categories</SelectItem
                                >
                                <SelectItem
                                    v-for="category in categories.data"
                                    :key="category.id"
                                    :value="String(category.id)"
                                >
                                    {{ category.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="text-sm font-medium">Account</label>
                        <Select v-model="filterDraft.chart_of_account_id">
                            <SelectTrigger>
                                <SelectValue placeholder="All accounts" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all"
                                    >All accounts</SelectItem
                                >
                                <SelectItem
                                    v-for="account in accounts.data"
                                    :key="account.id"
                                    :value="String(account.id)"
                                >
                                    {{ account.code }} — {{ account.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>
                <SheetFooter
                    class="border-t px-6 py-4 sm:flex-row sm:justify-between"
                >
                    <Button
                        type="button"
                        variant="ghost"
                        @click="filterDraft = emptyFilterDraft()"
                    >
                        Clear filters
                    </Button>
                    <div class="flex gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            @click="filterDrawerOpen = false"
                        >
                            Cancel
                        </Button>
                        <Button type="button" @click="applyFilters">
                            Apply filters
                        </Button>
                    </div>
                </SheetFooter>
            </SheetContent>
        </Sheet>
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
