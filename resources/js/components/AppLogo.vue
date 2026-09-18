<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
</script>

<template>
    <div
        class="flex aspect-square size-9 items-center justify-center rounded-xl bg-[#c7613a] text-white shadow-[0_6px_16px_rgba(153,67,35,0.24)]"
    >
        <AppLogoIcon class="size-5 text-white" />
    </div>
    <div class="ml-1.5 grid flex-1 text-left">
        <span class="truncate font-serif-cn text-[15px] leading-tight font-semibold tracking-[0.08em]">
            校园疗休养
        </span>
        <span class="mt-0.5 truncate text-[9px] tracking-[0.18em] text-sidebar-foreground/45 uppercase">
            Journey & Rest
        </span>
    </div>
</template>
