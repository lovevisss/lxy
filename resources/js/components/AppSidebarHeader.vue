<script setup lang="ts">
import { Bell, Search } from '@lucide/vue';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItem } from '@/types';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const page = usePage();
const user = computed(() => page.props.auth.user);
const accountLabel = computed(
    () =>
        [user.value.department, user.value.staff_number]
            .filter(Boolean)
            .join(' · ') || user.value.email,
);
</script>

<template>
    <header
        class="flex h-16 shrink-0 items-center justify-between gap-4 border-b border-[#dfded3]/80 bg-[#f8f7f0]/90 px-4 backdrop-blur-xl transition-[width,height] ease-linear md:px-7 dark:border-sidebar-border dark:bg-background/90"
    >
        <div class="flex items-center gap-2">
            <SidebarTrigger class="-ml-1" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>
        <div class="flex items-center gap-2">
            <label
                class="hidden h-9 w-56 items-center gap-2 rounded-full border border-[#deddd1] bg-white/70 px-3 text-xs text-[#77766d] lg:flex dark:border-border dark:bg-card"
            >
                <Search class="size-3.5" />
                <span>搜索线路、组团或通知</span>
                <kbd class="ml-auto text-[10px] text-[#aaa89d]">⌘ K</kbd>
            </label>
            <button
                type="button"
                class="relative grid size-9 place-items-center rounded-full border border-[#deddd1] bg-white/70 text-[#34584c] transition hover:-translate-y-0.5 hover:bg-white dark:border-border dark:bg-card"
                aria-label="消息通知"
            >
                <Bell class="size-4" />
                <span
                    class="absolute top-1.5 right-1.5 size-1.5 rounded-full bg-[#c7613a] ring-2 ring-[#f8f7f0]"
                />
            </button>
            <div class="ml-1 hidden text-right sm:block">
                <p
                    class="text-xs font-semibold text-[#243d35] dark:text-foreground"
                >
                    {{ user.name }}
                </p>
                <p class="max-w-44 truncate text-[10px] text-[#8b8a80]">
                    {{ accountLabel }}
                </p>
            </div>
        </div>
    </header>
</template>
