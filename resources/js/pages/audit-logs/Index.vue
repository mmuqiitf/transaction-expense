<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Eye, Filter, X } from '@lucide/vue';
import { useDebounceFn } from '@vueuse/core';
import { computed, h, ref } from 'vue';
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
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
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
import { formatDate } from '@/lib/format';
import auditLogRoutes from '@/routes/audit-logs';
import type { AuditLog, AuditLogUser, Paginated, SelectOption } from '@/types';

const props = defineProps<{
    logs: Paginated<AuditLog>;
    subjectTypes: SelectOption<string>[];
    events: string[];
    users: AuditLogUser[];
    filters: {
        search?: string | null;
        event?: string | null;
        subject_type?: string | null;
        causer_id?: number | null;
        from?: string | null;
        to?: string | null;
        sort?: string | null;
        direction?: 'asc' | 'desc';
    };
}>();

type AuditLogFilterKey =
    'search' | 'event' | 'subject_type' | 'causer_id' | 'from' | 'to';

type AuditLogFilterDraft = {
    event: string;
    subject_type: string;
    causer_id: string;
    from: string;
    to: string;
};

type ActiveFilter = {
    key: AuditLogFilterKey;
    label: string;
    value: string;
};

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Audit Logs', href: auditLogRoutes.index() }],
    },
});

const search = ref(props.filters.search ?? '');
const filterDrawerOpen = ref(false);
const selectedLog = ref<AuditLog | null>(null);
const detailModalOpen = ref(false);

const filterDraft = ref<AuditLogFilterDraft>(emptyFilterDraft());
const tableQuery = useTableQuery(auditLogRoutes.index.url);

function emptyFilterDraft(): AuditLogFilterDraft {
    return {
        event: 'all',
        subject_type: 'all',
        causer_id: 'all',
        from: '',
        to: '',
    };
}

function currentFilterQuery(): Record<string, string | number | undefined> {
    return {
        event: props.filters.event ?? undefined,
        subject_type: props.filters.subject_type ?? undefined,
        causer_id: props.filters.causer_id ?? undefined,
        from: props.filters.from ?? undefined,
        to: props.filters.to ?? undefined,
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
        event: props.filters.event ?? 'all',
        subject_type: props.filters.subject_type ?? 'all',
        causer_id: props.filters.causer_id
            ? String(props.filters.causer_id)
            : 'all',
        from: props.filters.from ?? '',
        to: props.filters.to ?? '',
    };
    filterDrawerOpen.value = true;
}

function applyFilters(): void {
    tableQuery.update({
        ...query(),
        filter: {
            event:
                filterDraft.value.event === 'all'
                    ? undefined
                    : filterDraft.value.event,
            subject_type:
                filterDraft.value.subject_type === 'all'
                    ? undefined
                    : filterDraft.value.subject_type,
            causer_id:
                filterDraft.value.causer_id === 'all'
                    ? undefined
                    : Number(filterDraft.value.causer_id),
            from: filterDraft.value.from || undefined,
            to: filterDraft.value.to || undefined,
        },
    });
    filterDrawerOpen.value = false;
}

function resetFilters(): void {
    search.value = '';
    tableQuery.update({});
}

function removeFilter(key: AuditLogFilterKey): void {
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

function userName(userId: number): string {
    return (
        props.users.find((user) => user.id === userId)?.name ??
        `User #${userId}`
    );
}

function subjectLabel(type: string): string {
    return (
        props.subjectTypes.find((item) => item.value === type)?.label ?? type
    );
}

function viewDetails(log: AuditLog): void {
    selectedLog.value = log;
    detailModalOpen.value = true;
}

const activeFilters = computed<ActiveFilter[]>(() => {
    const active: ActiveFilter[] = [];

    if (search.value) {
        active.push({ key: 'search', label: 'Search', value: search.value });
    }

    if (props.filters.event) {
        active.push({
            key: 'event',
            label: 'Event',
            value: props.filters.event,
        });
    }

    if (props.filters.subject_type) {
        active.push({
            key: 'subject_type',
            label: 'Subject',
            value: subjectLabel(props.filters.subject_type),
        });
    }

    if (props.filters.causer_id) {
        active.push({
            key: 'causer_id',
            label: 'Causer',
            value: userName(props.filters.causer_id),
        });
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

    return active;
});

const appliedFilterCount = computed(
    () =>
        activeFilters.value.filter((filter) => filter.key !== 'search').length,
);

function formatDateTime(isoString: string): string {
    if (!isoString) {
        return '—';
    }

    const date = new Date(isoString);

    return date.toLocaleString();
}

function eventBadgeVariant(
    event: string | null,
): 'default' | 'secondary' | 'destructive' | 'outline' {
    switch (event) {
        case 'created':
            return 'default';
        case 'updated':
            return 'secondary';
        case 'deleted':
            return 'destructive';
        default:
            return 'outline';
    }
}

const columns: DataTableColumnDef<AuditLog>[] = [
    {
        accessorKey: 'created_at',
        header: 'Date & Time',
        enableSorting: true,
        cell: ({ row }) =>
            h(
                'span',
                { class: 'text-sm text-muted-foreground whitespace-nowrap' },
                formatDateTime(row.original.created_at),
            ),
    },
    {
        id: 'causer',
        header: 'User',
        cell: ({ row }) => {
            const causer = row.original.causer;

            if (!causer) {
                return h(
                    'span',
                    { class: 'text-xs text-muted-foreground' },
                    'System',
                );
            }

            return h('div', { class: 'flex flex-col' }, [
                h('span', { class: 'font-medium text-sm' }, causer.name),
                h(
                    'span',
                    { class: 'text-xs text-muted-foreground' },
                    causer.email,
                ),
            ]);
        },
    },
    {
        accessorKey: 'event',
        header: 'Event',
        enableSorting: true,
        cell: ({ row }) =>
            h(
                Badge,
                {
                    variant: eventBadgeVariant(row.original.event),
                    class: 'capitalize',
                },
                () => row.original.event ?? 'event',
            ),
    },
    {
        id: 'subject',
        header: 'Subject',
        cell: ({ row }) => {
            const type = row.original.subject_type_name;
            const id = row.original.subject_id;

            if (!type) {
                return h('span', { class: 'text-muted-foreground' }, '—');
            }

            return h(
                'span',
                { class: 'font-medium text-sm' },
                `${type} #${id}`,
            );
        },
    },
    {
        accessorKey: 'description',
        header: 'Description',
        cell: ({ row }) =>
            h(
                'span',
                { class: 'text-sm max-w-xs truncate inline-block' },
                row.original.description,
            ),
    },
    {
        id: 'actions',
        header: '',
        cell: ({ row }) =>
            h('div', { class: 'flex justify-end' }, [
                h(
                    Button,
                    {
                        variant: 'ghost',
                        size: 'sm',
                        class: 'gap-1 text-xs',
                        onClick: () => viewDetails(row.original),
                    },
                    () => [h(Eye, { class: 'size-3.5' }), h('span', 'Details')],
                ),
            ]),
    },
];
</script>

<template>
    <Head title="Audit Logs" />
    <div class="flex flex-col gap-6">
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"
        >
            <Heading
                title="Audit Logs"
                description="Track changes, imports, and system activity recorded by the ledger."
            />
        </div>

        <DataTableToolbar
            :search="search"
            placeholder="Search description, event, or user..."
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
                    <SheetTitle>Filter audit logs</SheetTitle>
                    <SheetDescription>
                        Narrow logs by event, subject, user, or date range.
                    </SheetDescription>
                </SheetHeader>
                <div class="flex flex-col gap-6 px-6 py-6">
                    <div class="flex flex-col gap-2">
                        <label class="text-sm font-medium">Event</label>
                        <Select v-model="filterDraft.event">
                            <SelectTrigger>
                                <SelectValue placeholder="All events" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All events</SelectItem>
                                <SelectItem
                                    v-for="ev in events"
                                    :key="ev"
                                    :value="ev"
                                >
                                    {{ ev }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="flex flex-col gap-2">
                        <label class="text-sm font-medium">Subject</label>
                        <Select v-model="filterDraft.subject_type">
                            <SelectTrigger>
                                <SelectValue placeholder="All subjects" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all"
                                    >All subjects</SelectItem
                                >
                                <SelectItem
                                    v-for="sub in subjectTypes"
                                    :key="sub.value"
                                    :value="sub.value"
                                >
                                    {{ sub.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="flex flex-col gap-2">
                        <label class="text-sm font-medium">User / Causer</label>
                        <Select v-model="filterDraft.causer_id">
                            <SelectTrigger>
                                <SelectValue placeholder="All users" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All users</SelectItem>
                                <SelectItem
                                    v-for="u in users"
                                    :key="u.id"
                                    :value="String(u.id)"
                                >
                                    {{ u.name }} ({{ u.email }})
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="flex flex-col gap-2">
                        <label
                            for="audit-filter-from"
                            class="text-sm font-medium"
                            >From date</label
                        >
                        <Input
                            id="audit-filter-from"
                            v-model="filterDraft.from"
                            type="date"
                        />
                    </div>
                    <div class="flex flex-col gap-2">
                        <label for="audit-filter-to" class="text-sm font-medium"
                            >To date</label
                        >
                        <Input
                            id="audit-filter-to"
                            v-model="filterDraft.to"
                            type="date"
                        />
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

        <Dialog v-model:open="detailModalOpen">
            <DialogContent class="max-h-[80vh] max-w-2xl overflow-y-auto">
                <DialogHeader>
                    <DialogTitle
                        >Audit Log Details #{{ selectedLog?.id }}</DialogTitle
                    >
                    <DialogDescription>
                        {{ selectedLog?.description }}
                    </DialogDescription>
                </DialogHeader>

                <div
                    v-if="selectedLog"
                    class="mt-2 flex flex-col gap-4 text-sm"
                >
                    <div
                        class="grid grid-cols-2 gap-2 rounded-lg border bg-muted/20 p-3"
                    >
                        <div>
                            <span class="block text-xs text-muted-foreground"
                                >Event</span
                            >
                            <Badge
                                :variant="eventBadgeVariant(selectedLog.event)"
                                class="mt-0.5 capitalize"
                            >
                                {{ selectedLog.event }}
                            </Badge>
                        </div>
                        <div>
                            <span class="block text-xs text-muted-foreground"
                                >Date & Time</span
                            >
                            <span class="font-medium">{{
                                formatDateTime(selectedLog.created_at)
                            }}</span>
                        </div>
                        <div>
                            <span class="block text-xs text-muted-foreground"
                                >Subject</span
                            >
                            <span class="font-medium">
                                {{ selectedLog.subject_type_name ?? '—' }} #{{
                                    selectedLog.subject_id ?? '—'
                                }}
                            </span>
                        </div>
                        <div>
                            <span class="block text-xs text-muted-foreground"
                                >Causer</span
                            >
                            <span class="font-medium">
                                {{
                                    selectedLog.causer
                                        ? `${selectedLog.causer.name} (${selectedLog.causer.email})`
                                        : 'System'
                                }}
                            </span>
                        </div>
                    </div>

                    <div
                        v-if="
                            selectedLog.properties &&
                            Object.keys(selectedLog.properties).length
                        "
                        class="flex flex-col gap-3"
                    >
                        <h4 class="text-sm font-semibold">
                            Changed Properties
                        </h4>
                        <div
                            class="overflow-x-auto rounded-lg border bg-muted/40 p-4 font-mono text-xs"
                        >
                            <pre>{{
                                JSON.stringify(selectedLog.properties, null, 2)
                            }}</pre>
                        </div>
                    </div>
                </div>
            </DialogContent>
        </Dialog>

        <div class="rounded-xl border">
            <DataTable
                :columns="columns"
                :data="logs.data"
                :sort="filters.sort"
                :direction="filters.direction"
                @sort="
                    (sort, direction) =>
                        tableQuery.update(query({ sort, direction }))
                "
            />
        </div>
        <DataTablePagination
            :meta="logs.meta"
            @page="(page) => tableQuery.visit(query({ page }))"
            @per-page="
                (perPage) => tableQuery.update(query({ per_page: perPage }))
            "
        />
    </div>
</template>
