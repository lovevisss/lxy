<script setup lang="ts">
import { MapPin } from '@lucide/vue';

defineProps<{
    palette: string;
    location: string;
    accent?: string;
    image?: string;
}>();
</script>

<template>
    <div
        :class="[
            'contour-lines relative overflow-hidden bg-gradient-to-br',
            palette,
        ]"
    >
        <img
            v-if="image"
            :src="image"
            :alt="`${location}线路封面`"
            class="absolute inset-0 size-full object-cover transition duration-700 group-hover:scale-[1.03]"
        />
        <div
            v-if="image"
            class="absolute inset-0 bg-gradient-to-t from-[#102f29]/65 via-transparent to-[#102f29]/12"
        />
        <div v-else class="absolute inset-x-0 bottom-0 h-2/3 opacity-90">
            <svg
                viewBox="0 0 600 220"
                class="size-full"
                preserveAspectRatio="none"
                aria-hidden="true"
            >
                <path
                    d="M0 185 95 80l72 72L254 40l105 118 65-55 176 82v35H0Z"
                    fill="rgba(13,49,42,.34)"
                />
                <path
                    d="M0 201 128 133l83 45 126-89 101 88 68-35 94 58v20H0Z"
                    fill="rgba(11,39,34,.25)"
                />
            </svg>
        </div>
        <div
            class="absolute inset-x-4 top-4 flex items-center justify-between text-white/85"
        >
            <span
                class="inline-flex items-center gap-1 rounded-full bg-black/10 px-2.5 py-1 text-[10px] backdrop-blur-sm"
            >
                <MapPin class="size-3" /> {{ location }}
            </span>
            <span
                class="font-serif-cn text-lg"
                :style="{ color: accent || '#fff' }"
                >游</span
            >
        </div>
        <slot />
    </div>
</template>
