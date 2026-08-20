<script setup lang="ts">
import { Head, Form, Link } from '@inertiajs/vue3';
import ChartOfAccountController from '@/actions/App/Http/Controllers/ChartOfAccountController';
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
import accountRoutes from '@/routes/chart-of-accounts';
import type { Category, ChartOfAccount } from '@/types';

const props = defineProps<{
    account: ChartOfAccount;
    categories: { data: Category[] };
}>();
defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Chart of Accounts', href: accountRoutes.index() },
            { title: 'Edit', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="`Edit ${account.name}`" />
    <div class="flex max-w-2xl flex-col gap-6">
        <Heading
            title="Edit chart of account"
            description="Update the account details."
        />
        <Form
            v-bind="ChartOfAccountController.update.form(props.account.id)"
            class="grid gap-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="code">Code</Label
                ><Input
                    id="code"
                    name="code"
                    required
                    :default-value="account.code"
                /><InputError :message="errors.code" />
            </div>
            <div class="grid gap-2">
                <Label for="name">Name</Label
                ><Input
                    id="name"
                    name="name"
                    required
                    :default-value="account.name"
                /><InputError :message="errors.name" />
            </div>
            <div class="grid gap-2">
                <Label for="category_id">Category</Label
                ><Select
                    name="category_id"
                    required
                    :default-value="String(account.category.id)"
                    ><SelectTrigger id="category_id"
                        ><SelectValue /></SelectTrigger
                    ><SelectContent
                        ><SelectItem
                            v-for="category in categories.data"
                            :key="category.id"
                            :value="String(category.id)"
                            >{{ category.name }}</SelectItem
                        ></SelectContent
                    ></Select
                ><InputError :message="errors.category_id" />
            </div>
            <div class="flex gap-2">
                <Button :disabled="processing">Save changes</Button
                ><Button as-child variant="outline"
                    ><Link :href="accountRoutes.index()">Cancel</Link></Button
                >
            </div>
        </Form>
    </div>
</template>
