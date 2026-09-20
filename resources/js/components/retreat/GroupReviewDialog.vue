<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { MessageSquareText, Star } from '@lucide/vue';
import { computed, reactive, ref } from 'vue';
import { toast } from 'vue-sonner';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';

type GroupReviewValue = {
    id?: number;
    route_score: number;
    meal_score: number;
    attraction_score: number;
    accommodation_score: number;
    service_score: number;
    comment?: string | null;
};

const props = defineProps<{
    routeId: number;
    sourceGroupId: number;
    routeTitle: string;
    currentReview?: GroupReviewValue | null;
}>();

const emit = defineEmits<{ submitted: [] }>();
const open = ref(false);
const processing = ref(false);
const scores = reactive({
    route_score: props.currentReview?.route_score ?? 0,
    meal_score: props.currentReview?.meal_score ?? 0,
    attraction_score: props.currentReview?.attraction_score ?? 0,
    accommodation_score: props.currentReview?.accommodation_score ?? 0,
    service_score: props.currentReview?.service_score ?? 0,
});
const comment = ref(props.currentReview?.comment ?? '');
const dimensions = [
    {
        key: 'route_score' as const,
        label: '路线安排',
        description: '节奏、交通衔接与每日行程强度',
    },
    {
        key: 'meal_score' as const,
        label: '团餐安排',
        description: '口味、品类、卫生与地方特色',
    },
    {
        key: 'attraction_score' as const,
        label: '景点体验',
        description: '景点选择、游览时间与讲解质量',
    },
    {
        key: 'accommodation_score' as const,
        label: '住宿体验',
        description: '酒店环境、舒适度与位置',
    },
    {
        key: 'service_score' as const,
        label: '组织服务',
        description: '团长协调、通知与应急保障',
    },
];
const overall = computed(() => {
    const values = Object.values(scores);

    if (values.some((value) => value === 0)) {
        return null;
    }

    return (
        values.reduce((sum, value) => sum + value, 0) / values.length
    ).toFixed(1);
});

function submit() {
    if (!overall.value) {
        toast.error('请完成全部五项评分');

        return;
    }

    router.post(
        `/routes/${props.routeId}/reviews`,
        {
            retreat_group_id: props.sourceGroupId,
            ...scores,
            comment: comment.value,
        },
        {
            preserveScroll: true,
            onStart: () => (processing.value = true),
            onFinish: () => (processing.value = false),
            onError: (errors) =>
                toast.error('评价提交失败', {
                    description:
                        Object.values(errors)[0] ??
                        '请确认各项评分均为 1—5 分。',
                }),
            onSuccess: () => {
                open.value = false;
                toast.success(
                    props.currentReview ? '评价已更新' : '感谢您的评价',
                    { description: '您的反馈将用于改进后续疗休养安排。' },
                );
                emit('submitted');
            },
        },
    );
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogTrigger as-child>
            <slot />
        </DialogTrigger>
        <DialogContent
            class="max-h-[90vh] overflow-y-auto border-[#ddd8ca] bg-[#fffefa] p-0 sm:max-w-2xl"
        >
            <div
                class="contour-lines relative overflow-hidden bg-[#173f35] px-6 py-6 text-white sm:px-8"
            >
                <DialogHeader class="relative z-10">
                    <p
                        class="text-[10px] font-semibold tracking-[.2em] text-[#edbf79] uppercase"
                    >
                        Journey review
                    </p>
                    <DialogTitle class="font-serif-cn mt-2 text-2xl text-white">
                        评价这条疗休养线路
                    </DialogTitle>
                    <DialogDescription
                        class="mt-2 text-xs leading-6 text-white/60"
                    >
                        {{
                            routeTitle
                        }}。您的真实反馈将帮助学校优化后续线路与服务。
                    </DialogDescription>
                </DialogHeader>
                <div
                    v-if="overall"
                    class="absolute right-8 bottom-6 hidden text-right sm:block"
                >
                    <p
                        class="font-serif-cn text-4xl font-semibold text-[#f0c37f]"
                    >
                        {{ overall }}
                    </p>
                    <p class="mt-1 text-[10px] text-white/50">当前综合评分</p>
                </div>
            </div>

            <form class="px-6 pb-6 sm:px-8 sm:pb-8" @submit.prevent="submit">
                <div class="mt-6 divide-y divide-[#e8e3d7]">
                    <div
                        v-for="dimension in dimensions"
                        :key="dimension.key"
                        class="grid gap-3 py-4 sm:grid-cols-[1fr_auto] sm:items-center"
                    >
                        <div>
                            <p class="text-sm font-semibold text-[#315348]">
                                {{ dimension.label }}
                            </p>
                            <p class="mt-1 text-[10px] text-[#918f85]">
                                {{ dimension.description }}
                            </p>
                        </div>
                        <div class="flex gap-1" :aria-label="dimension.label">
                            <button
                                v-for="score in 5"
                                :key="score"
                                type="button"
                                class="grid size-8 place-items-center rounded-full transition hover:bg-[#f3eadb]"
                                :aria-label="`${score} 分`"
                                @click="scores[dimension.key] = score"
                            >
                                <Star
                                    :class="[
                                        'size-5 transition',
                                        score <= scores[dimension.key]
                                            ? 'fill-[#d99a45] text-[#d99a45]'
                                            : 'text-[#cdc8bc]',
                                    ]"
                                />
                            </button>
                        </div>
                    </div>
                </div>

                <label class="mt-5 block">
                    <span
                        class="mb-2 flex items-center gap-2 text-xs font-semibold text-[#315348]"
                        ><MessageSquareText class="size-3.5 text-[#b65f40]" />
                        体验感受与建议</span
                    >
                    <textarea
                        v-model="comment"
                        rows="4"
                        maxlength="2000"
                        class="w-full resize-none rounded-2xl border border-[#d9d4c7] bg-[#faf9f3] p-4 text-sm leading-6 outline-none focus:border-[#4e7568]"
                        placeholder="哪些安排让您印象深刻？哪些方面还可以改善？"
                    />
                </label>

                <div
                    class="mt-6 flex flex-col gap-3 border-t border-[#e5e0d4] pt-5 sm:flex-row sm:items-center sm:justify-between"
                >
                    <p class="text-xs text-[#85847c]">提交后仍可回来修改评价</p>
                    <button
                        type="submit"
                        :disabled="processing || !overall"
                        class="h-11 rounded-full bg-[#c46140] px-7 text-sm font-semibold text-white transition hover:bg-[#b45637] disabled:cursor-not-allowed disabled:opacity-45"
                    >
                        {{ processing ? '正在保存…' : '提交体验评价' }}
                    </button>
                </div>
            </form>
        </DialogContent>
    </Dialog>
</template>
