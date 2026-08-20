<script setup lang="ts">
import { ChevronLeft, ChevronRight } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { PaginationMeta } from '@/types';

defineProps<{
    meta: PaginationMeta;
}>();

const emit = defineEmits<{
    page: [page: number];
    perPage: [perPage: number];
}>();

const perPageOptions = [15, 25, 50, 100];
</script>

<template>
    <div
        class="flex flex-col gap-3 border-t pt-4 text-sm text-muted-foreground sm:flex-row sm:items-center sm:justify-between"
    >
        <p>
            Showing {{ meta.from ?? 0 }} to {{ meta.to ?? 0 }} of
            {{ meta.total }} records
        </p>
        <div class="flex flex-wrap items-center gap-4">
            <div class="flex items-center gap-2">
                <span>Per page</span>
                <Select
                    :model-value="String(meta.per_page)"
                    @update:model-value="emit('perPage', Number($event))"
                >
                    <SelectTrigger class="w-20">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="option in perPageOptions"
                            :key="option"
                            :value="String(option)"
                        >
                            {{ option }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>
            <div class="flex items-center gap-1">
                <Button
                    type="button"
                    variant="outline"
                    size="icon"
                    :disabled="meta.current_page <= 1"
                    aria-label="Previous page"
                    @click="emit('page', meta.current_page - 1)"
                >
                    <ChevronLeft class="size-4" />
                </Button>
                <span class="min-w-20 text-center"
                    >Page {{ meta.current_page }} of {{ meta.last_page }}</span
                >
                <Button
                    type="button"
                    variant="outline"
                    size="icon"
                    :disabled="meta.current_page >= meta.last_page"
                    aria-label="Next page"
                    @click="emit('page', meta.current_page + 1)"
                >
                    <ChevronRight class="size-4" />
                </Button>
            </div>
        </div>
    </div>
</template>
