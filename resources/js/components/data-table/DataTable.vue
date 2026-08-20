<script setup lang="ts" generic="TData extends RowData">
import { ArrowDown, ArrowUp, ArrowUpDown } from '@lucide/vue';
import { FlexRender, useTable } from '@tanstack/vue-table';
import type { RowData } from '@tanstack/vue-table';
import { computed } from 'vue';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { dataTableFeatures } from './types';
import type { DataTableColumnDef } from './types';

const props = withDefaults(
    defineProps<{
        columns: DataTableColumnDef<TData>[];
        data: TData[];
        sort?: string | null;
        direction?: 'asc' | 'desc';
        emptyMessage?: string;
    }>(),
    {
        sort: null,
        direction: 'asc',
        emptyMessage: 'No records found.',
    },
);

const emit = defineEmits<{
    sort: [column: string, direction: 'asc' | 'desc'];
}>();

const table = useTable({
    features: dataTableFeatures,
    get data() {
        return props.data;
    },
    get columns() {
        return props.columns;
    },
    manualSorting: true,
});

const hasRows = computed(() => table.getRowModel().rows.length > 0);

function toggleSort(columnId: string): void {
    const direction =
        props.sort === columnId && props.direction === 'asc' ? 'desc' : 'asc';

    emit('sort', columnId, direction);
}
</script>

<template>
    <Table>
        <TableHeader>
            <TableRow
                v-for="headerGroup in table.getHeaderGroups()"
                :key="headerGroup.id"
            >
                <TableHead
                    v-for="header in headerGroup.headers"
                    :key="header.id"
                >
                    <button
                        v-if="header.column.columnDef.enableSorting"
                        type="button"
                        class="inline-flex items-center gap-2 font-medium"
                        @click="toggleSort(header.column.id)"
                    >
                        <FlexRender
                            :render="header.column.columnDef.header"
                            :props="header.getContext()"
                        />
                        <ArrowUp
                            v-if="
                                props.sort === header.column.id &&
                                props.direction === 'asc'
                            "
                            class="size-3.5"
                        />
                        <ArrowDown
                            v-else-if="props.sort === header.column.id"
                            class="size-3.5"
                        />
                        <ArrowUpDown v-else class="size-3.5 opacity-50" />
                    </button>
                    <FlexRender
                        v-else
                        :render="header.column.columnDef.header"
                        :props="header.getContext()"
                    />
                </TableHead>
            </TableRow>
        </TableHeader>
        <TableBody>
            <template v-if="hasRows">
                <TableRow v-for="row in table.getRowModel().rows" :key="row.id">
                    <TableCell v-for="cell in row.getAllCells()" :key="cell.id">
                        <FlexRender
                            :render="cell.column.columnDef.cell"
                            :props="cell.getContext()"
                        />
                    </TableCell>
                </TableRow>
            </template>
            <TableRow v-else>
                <TableCell
                    :colspan="props.columns.length"
                    class="h-24 text-center text-muted-foreground"
                >
                    {{ emptyMessage }}
                </TableCell>
            </TableRow>
        </TableBody>
    </Table>
</template>
