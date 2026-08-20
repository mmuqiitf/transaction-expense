<script setup lang="ts">
import { Head, Form, Link } from '@inertiajs/vue3';
import CategoryController from '@/actions/App/Http/Controllers/CategoryController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import categoryRoutes from '@/routes/categories';
import type { Category, CategoryType, SelectOption } from '@/types';

const props = defineProps<{
    category: Category;
    categoryTypes: SelectOption<CategoryType>[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Categories', href: categoryRoutes.index() },
            { title: 'Edit', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="`Edit ${category.name}`" />
    <div class="flex max-w-2xl flex-col gap-6">
        <Heading
            title="Edit category"
            description="Update the category details."
        />
        <Form
            v-bind="CategoryController.update.form(props.category.id)"
            class="grid gap-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="name">Name</Label>
                <Input
                    id="name"
                    name="name"
                    required
                    :default-value="category.name"
                />
                <InputError :message="errors.name" />
            </div>
            <div class="grid gap-2">
                <Label for="type">Classification</Label>
                <Select name="type" required :default-value="category.type">
                    <SelectTrigger id="type"
                        ><SelectValue placeholder="Select classification"
                    /></SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="option in categoryTypes"
                            :key="option.value"
                            :value="option.value"
                            >{{ option.label }}</SelectItem
                        >
                    </SelectContent>
                </Select>
                <InputError :message="errors.type" />
            </div>
            <div class="flex gap-2">
                <Button :disabled="processing">Save changes</Button>
                <Button as-child variant="outline"
                    ><Link :href="categoryRoutes.index()">Cancel</Link></Button
                >
            </div>
        </Form>
    </div>
</template>
