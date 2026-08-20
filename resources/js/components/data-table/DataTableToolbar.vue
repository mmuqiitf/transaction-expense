<script setup lang="ts">
import { Search, X } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

defineProps<{
    search?: string | null;
    placeholder?: string;
    hasActiveFilters?: boolean;
}>();

const emit = defineEmits<{
    'update:search': [value: string];
    reset: [];
}>();
</script>

<template>
    <div
        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
    >
        <div class="relative w-full sm:max-w-sm">
            <Search
                class="absolute top-2.5 left-2.5 size-4 text-muted-foreground"
            />
            <Input
                :model-value="search ?? ''"
                :placeholder="placeholder ?? 'Search...'"
                class="pl-8"
                @update:model-value="emit('update:search', String($event))"
            />
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <slot name="filters" />
            <Button
                v-if="hasActiveFilters"
                type="button"
                variant="ghost"
                size="sm"
                @click="emit('reset')"
            >
                Reset
                <X class="ml-1 size-4" />
            </Button>
            <slot name="actions" />
        </div>
    </div>
</template>
