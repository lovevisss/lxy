<script setup lang="ts">
defineProps<{
    eyebrow: string;
    title: string;
    description: string;
}>();
</script>

<template>
    <div
        class="enter-up flex flex-col justify-between gap-5 md:flex-row md:items-end"
    >
        <div>
            <p
                class="mb-2 text-[10px] font-semibold tracking-[0.22em] text-[#b55e3d] uppercase"
            >
                {{ eyebrow }}
            </p>
            <h1
                class="font-serif-cn text-3xl font-semibold tracking-tight text-[#193d34] md:text-4xl dark:text-foreground"
            >
                {{ title }}
            </h1>
            <p
                class="mt-3 max-w-2xl text-sm leading-6 text-[#72736b] dark:text-muted-foreground"
            >
                {{ description }}
            </p>
        </div>
        <slot />
    </div>
</template>
