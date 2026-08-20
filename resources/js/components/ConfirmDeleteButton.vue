<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';

const props = withDefaults(
    defineProps<{
        url: string;
        title?: string;
        description?: string;
    }>(),
    {
        title: 'Delete this record?',
        description: 'This action cannot be undone.',
    },
);

const open = ref(false);
const processing = ref(false);

function destroy(): void {
    processing.value = true;

    router.delete(props.url, {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
        },
        onFinish: () => {
            processing.value = false;
        },
    });
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogTrigger as-child>
            <Button
                type="button"
                variant="ghost"
                size="sm"
                class="text-destructive hover:text-destructive"
            >
                Delete
            </Button>
        </DialogTrigger>
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{{ title }}</DialogTitle>
                <DialogDescription>{{ description }}</DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <DialogClose as-child>
                    <Button
                        type="button"
                        variant="outline"
                        :disabled="processing"
                        >Cancel</Button
                    >
                </DialogClose>
                <Button
                    type="button"
                    variant="destructive"
                    :disabled="processing"
                    @click="destroy"
                >
                    {{ processing ? 'Deleting...' : 'Delete' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
