<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    BadgeCheck,
    BookOpenText,
    Compass,
    LayoutDashboard,
    LifeBuoy,
    MapPinned,
    UsersRound,
} from '@lucide/vue';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
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
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: '工作台',
        href: '/dashboard',
        icon: LayoutDashboard,
    },
    {
        title: '线路库',
        href: '/routes',
        icon: MapPinned,
    },
    {
        title: '组团广场',
        href: '/groups',
        icon: UsersRound,
    },
    {
        title: '审批中心',
        href: '/approvals',
        icon: BadgeCheck,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: '办事指南',
        href: '#',
        icon: BookOpenText,
    },
    {
        title: '帮助反馈',
        href: '#',
        icon: LifeBuoy,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link href="/dashboard">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent class="pt-3">
            <NavMain :items="mainNavItems" />
            <div
                class="mx-3 mt-6 overflow-hidden rounded-2xl bg-[#173e35] p-4 text-white shadow-sm group-data-[collapsible=icon]:hidden"
            >
                <Compass class="mb-4 size-5 text-[#e9b66d]" />
                <p class="font-serif-cn text-sm font-semibold">秋季疗休养季</p>
                <p class="mt-1 text-[11px] leading-5 text-white/60">
                    9 条线路开放报名，去山水间重新找回节奏。
                </p>
                <Link
                    href="/groups"
                    class="mt-4 inline-flex text-xs font-medium text-[#f0c98f]"
                >
                    去看看 →
                </Link>
            </div>
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
