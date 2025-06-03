<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { User, Table, LayoutGrid, Package, Tags, Users } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';
import { usePage } from '@inertiajs/vue3';

const user = usePage().props.user as { role: string };
let mainNavItems: NavItem[] = [];

const navConfig: Record<string, NavItem[]> = {
    kasir: [
        { title: 'Dashboard', href: '/dashboard', icon: LayoutGrid },
        { title: 'Kasir', href: '/kasir', icon: Users },
    ],
    pramuniaga: [
        { title: 'Pramuniaga', href: '/pramuniaga', icon: Users },
        { title: 'Dashboard', href: '/dashboard', icon: LayoutGrid },
    ],
    admin: [
        { title: 'Dashboard', href: '/dashboard', icon: LayoutGrid },
        { title: 'Data Produk', href: '/produk', icon: Package },
        { title: 'Data Kategori', href: '/kategori', icon: Tags },
        { title: 'Data Merek', href: '/merek', icon: Tags },
        { title: 'Data Pelanggan', href: '/pelanggan', icon: User },
        { title: 'Data Transaksi', href: '/datatransaksi', icon: Table },
        { title: 'Data User', href: '/user', icon: Users },
    ],
};

mainNavItems = navConfig[user.role] || [];


const footerNavItems: NavItem[] = [

];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="route('dashboard')">
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
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
