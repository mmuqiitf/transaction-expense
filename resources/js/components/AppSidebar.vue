<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    BookOpen,
    ChartColumn,
    History,
    LayoutGrid,
    Receipt,
    Tags,
} from '@lucide/vue';
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import auditLogRoutes from '@/routes/audit-logs';
import categoryRoutes from '@/routes/categories';
import accountRoutes from '@/routes/chart-of-accounts';
import reportRoutes from '@/routes/reports';
import transactionRoutes from '@/routes/transactions';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Categories',
        href: categoryRoutes.index(),
        icon: Tags,
    },
    {
        title: 'Chart of Accounts',
        href: accountRoutes.index(),
        icon: BookOpen,
    },
    {
        title: 'Transactions',
        href: transactionRoutes.index(),
        icon: Receipt,
    },
    {
        title: 'Profit/Loss',
        href: reportRoutes.profitLoss.index(),
        icon: ChartColumn,
    },
    {
        title: 'Audit Logs',
        href: auditLogRoutes.index(),
        icon: History,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
