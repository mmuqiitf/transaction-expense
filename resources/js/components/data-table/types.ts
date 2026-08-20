import { rowSortingFeature, tableFeatures } from '@tanstack/vue-table';
import type { ColumnDef, RowData } from '@tanstack/vue-table';

export const dataTableFeatures = tableFeatures({ rowSortingFeature });

export type DataTableColumnDef<
    TData extends RowData,
    TValue = unknown,
> = ColumnDef<typeof dataTableFeatures, TData, TValue>;
