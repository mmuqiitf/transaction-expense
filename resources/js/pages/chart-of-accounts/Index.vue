<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { h, ref } from 'vue';
import ChartOfAccountController from '@/actions/App/Http/Controllers/ChartOfAccountController';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useTableQuery } from '@/composables/useTableQuery';
import accountRoutes from '@/routes/chart-of-accounts';
import type {
    Category,
    CategoryType,
    ChartOfAccount,
    Paginated,
    SelectOption,
} from '@/types';

const props = defineProps<{
    accounts: Paginated<ChartOfAccount>;
    categories: { data: Category[] };
    categoryTypes: SelectOption<CategoryType>[];
    filters: {
        search?: string | null;
        category_id?: number | null;
        type?: CategoryType | null;
        sort?: string | null;
        direction?: 'asc' | 'desc';
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Chart of Accounts', href: accountRoutes.index() },
        ],
    },
});

const search = ref(props.filters.search ?? '');
const tableQuery = useTableQuery(accountRoutes.index.url);

function query(overrides: Record<string, unknown> = {}) {
    return {
        search: search.value || undefined,
        sort: props.filters.sort ?? undefined,
        direction: props.filters.direction ?? undefined,
        filter: {
            category_id: props.filters.category_id ?? undefined,
            type: props.filters.type ?? undefined,
        },
        ...overrides,
    };
}

const updateSearch = useDebounceFn((value: string) => {
    search.value = value;
    tableQuery.update(query());
}, 300);

function updateCategory(value: string): void {
    tableQuery.update({
        ...query(),
        filter: {
            category_id: value === 'all' ? undefined : Number(value),
            type: props.filters.type ?? undefined,
        },
    });
}

function updateType(value: string): void {
    tableQuery.update({
        ...query(),
        filter: {
            category_id: props.filters.category_id ?? undefined,
            type: value === 'all' ? undefined : value,
        },
    });
}

function resetFilters(): void {
    search.value = '';
    tableQuery.update({});
}

const columns: DataTableColumnDef<ChartOfAccount>[] = [
    { accessorKey: 'code', header: 'Code', enableSorting: true },
    { accessorKey: 'name', header: 'Name', enableSorting: true },
    {
        id: 'category',
        header: 'Category',
        cell: ({ row }) => row.original.category.name,
    },
    {
        id: 'type',
        header: 'Classification',
        cell: ({ row }) =>
            h(
                Badge,
                {
                    variant:
                        row.original.category.type === 'income'
                            ? 'default'
                            : 'secondary',
                },
                () => row.original.category.type,
            ),
    },
    { accessorKey: 'transactions_count', header: 'Transactions' },
    {
        id: 'actions',
        header: '',
        cell: ({ row }) =>
            h('div', { class: 'flex justify-end gap-1' }, [
                h(
                    Link,
                    {
                        href: accountRoutes.edit.url(row.original.id),
                        class: 'inline-flex items-center rounded-md px-2 py-1 text-sm hover:bg-muted',
                    },
                    () => 'Edit',
                ),
                h(ConfirmDeleteButton, {
                    url: ChartOfAccountController.destroy.url(row.original.id),
                }),
            ]),
    },
];
</script>

<template>
    <Head title="Chart of Accounts" />
    <div class="flex flex-col gap-6">
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"
        >
            <Heading
                title="Chart of Accounts"
                description="Manage account codes and their classifications."
            />
            <Button as-child
                ><Link :href="accountRoutes.create()">Add account</Link></Button
            >
        </div>
        <DataTableToolbar
            :search="search"
            placeholder="Search code or account name..."
            :has-active-filters="
                Boolean(search || filters.category_id || filters.type)
            "
            @update:search="updateSearch"
            @reset="resetFilters"
        >
            <template #filters>
                <Select
                    :model-value="
                        filters.category_id
                            ? String(filters.category_id)
                            : 'all'
                    "
                    @update:model-value="updateCategory(String($event))"
                >
                    <SelectTrigger class="w-[170px]"
                        ><SelectValue placeholder="Category"
                    /></SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All categories</SelectItem>
                        <SelectItem
                            v-for="category in categories.data"
                            :key="category.id"
                            :value="String(category.id)"
                            >{{ category.name }}</SelectItem
                        >
                    </SelectContent>
                </Select>
                <Select
                    :model-value="filters.type ?? 'all'"
                    @update:model-value="updateType(String($event))"
                >
                    <SelectTrigger class="w-[170px]"
                        ><SelectValue placeholder="Classification"
                    /></SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All classifications</SelectItem>
                        <SelectItem
                            v-for="option in categoryTypes"
                            :key="option.value"
                            :value="option.value"
                            >{{ option.label }}</SelectItem
                        >
                    </SelectContent>
                </Select>
            </template>
        </DataTableToolbar>
        <div class="rounded-xl border">
            <DataTable
                :columns="columns"
                :data="accounts.data"
                :sort="filters.sort"
                :direction="filters.direction"
                @sort="
                    (sort, direction) =>
                        tableQuery.update(query({ sort, direction }))
                "
            />
        </div>
        <DataTablePagination
            :meta="accounts.meta"
            @page="(page) => tableQuery.visit(query({ page }))"
            @per-page="
                (perPage) => tableQuery.update(query({ per_page: perPage }))
            "
        />
    </div>
</template>
