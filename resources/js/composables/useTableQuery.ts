import { router } from '@inertiajs/vue3';
import type { QueryParams } from '@/wayfinder';

export interface TableQuery {
    page?: number;
    per_page?: number;
    search?: string;
    sort?: string;
    direction?: 'asc' | 'desc';
    filter?: Record<string, string | number | null | undefined>;
}

export function useTableQuery(
    routeUrl: (options?: { query?: QueryParams }) => string,
) {
    function visit(query: TableQuery): void {
        router.get(
            routeUrl({ query: query as QueryParams }),
            {},
            {
                preserveScroll: true,
                preserveState: true,
                replace: true,
            },
        );
    }

    function update(query: TableQuery): void {
        visit({ ...query, page: 1 });
    }

    return { update, visit };
}
