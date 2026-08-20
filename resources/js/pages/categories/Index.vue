<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { h, ref } from 'vue';
import CategoryController from '@/actions/App/Http/Controllers/CategoryController';
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
import categoryRoutes from '@/routes/categories';
import type { Category, CategoryType, Paginated, SelectOption } from '@/types';

const props = defineProps<{
    categories: Paginated<Category>;
    filters: {
        search?: string | null;
        type?: CategoryType | null;
        sort?: string | null;
        direction?: 'asc' | 'desc';
    };
    categoryTypes: SelectOption<CategoryType>[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Categories', href: categoryRoutes.index() }],
    },
});

const search = ref(props.filters.search ?? '');
const tableQuery = useTableQuery(categoryRoutes.index.url);

function query(overrides: Record<string, unknown> = {}) {
    return {
        search: search.value || undefined,
        sort: props.filters.sort ?? undefined,
        direction: props.filters.direction ?? undefined,
        filter: { type: props.filters.type ?? undefined },
        ...overrides,
    };
}

const updateSearch = useDebounceFn((value: string) => {
    search.value = value;
    tableQuery.update(query());
}, 300);

function updateType(value: string): void {
    tableQuery.update({
        ...query(),
        filter: { type: value === 'all' ? undefined : value },
    });
}

function resetFilters(): void {
    search.value = '';
    tableQuery.update({});
}

const columns: DataTableColumnDef<Category>[] = [
    { accessorKey: 'name', header: 'Name', enableSorting: true },
    {
        accessorKey: 'type',
        header: 'Classification',
        enableSorting: true,
        cell: ({ row }) =>
            h(
                Badge,
                {
                    variant:
                        row.original.type === 'income'
                            ? 'default'
                            : 'secondary',
                },
                () => row.original.type,
            ),
    },
    { accessorKey: 'chart_of_accounts_count', header: 'COA entries' },
    {
        id: 'actions',
        header: '',
        cell: ({ row }) =>
            h('div', { class: 'flex justify-end gap-1' }, [
                h(
                    Link,
                    {
                        href: categoryRoutes.edit.url(row.original.id),
                        class: 'inline-flex items-center rounded-md px-2 py-1 text-sm hover:bg-muted',
                    },
                    () => 'Edit',
                ),
                h(ConfirmDeleteButton, {
                    url: CategoryController.destroy.url(row.original.id),
                }),
            ]),
    },
];
</script>

<template>
    <Head title="Categories" />
    <div class="flex flex-col gap-6">
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"
        >
            <Heading
                title="Categories"
                description="Classify income and expense accounts."
            />
            <Button as-child>
                <Link :href="categoryRoutes.create()">Add category</Link>
            </Button>
        </div>
        <DataTableToolbar
            :search="search"
            placeholder="Search categories..."
            :has-active-filters="Boolean(search || filters.type)"
            @update:search="updateSearch"
            @reset="resetFilters"
        >
            <template #filters>
                <Select
                    :model-value="filters.type ?? 'all'"
                    @update:model-value="updateType(String($event))"
                >
                    <SelectTrigger class="w-[170px]">
                        <SelectValue placeholder="Classification" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All classifications</SelectItem>
                        <SelectItem
                            v-for="option in categoryTypes"
                            :key="option.value"
                            :value="option.value"
                        >
                            {{ option.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </template>
        </DataTableToolbar>
        <div class="rounded-xl border">
            <DataTable
                :columns="columns"
                :data="categories.data"
                :sort="filters.sort"
                :direction="filters.direction"
                @sort="
                    (sort, direction) =>
                        tableQuery.update(query({ sort, direction }))
                "
            />
        </div>
        <DataTablePagination
            :meta="categories.meta"
            @page="(page) => tableQuery.visit(query({ page }))"
            @per-page="
                (perPage) => tableQuery.update(query({ per_page: perPage }))
            "
        />
    </div>
</template>
