<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <svg viewBox="0 0 32 32" :class="className" v-bind="$attrs">
        <path
            fill="none"
            stroke="currentColor"
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M4 23.5 11.2 13l4.3 6.1L20.8 9 28 23.5M3.5 24h25M10 27h12"
        />
        <path
            fill="currentColor"
            d="M22.8 5.2a3.2 3.2 0 1 1 0 6.4 4.3 4.3 0 0 0 0-6.4Z"
        />
    </svg>
</template>
