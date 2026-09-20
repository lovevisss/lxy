<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    BadgeCheck,
    ChevronRight,
    CircleAlert,
    CircleDollarSign,
    Clock3,
    FileDiff,
    Filter,
    Search,
    Plane,
} from '@lucide/vue';
import { toast } from 'vue-sonner';
import PageIntro from '@/components/retreat/PageIntro.vue';

defineOptions({
    layout: { breadcrumbs: [{ title: '审批中心', href: '/approvals' }] },
});

type ApprovalRecord = {
    id: number;
    routeId?: number;
    type: string;
    title: string;
    applicant: string;
    department: string;
    time: string;
    stay: string;
    status: string;
    days: number;
    people: string;
    location: string;
    summary: string;
    dailySubsidy: number;
    estimatedSubsidy: number;
    selfFundedItems: string[];
    stage: 'department' | 'union';
    decision?: 'approved' | 'rejected';
    comment?: string | null;
    itinerary: { day: number; title: string }[];
};

const props = defineProps<{
    approvalRecords?: ApprovalRecord[];
    approvalHistory?: ApprovalRecord[];
}>();

const active = ref('待我审批');
const comment = ref('');
const items = computed(() =>
    active.value === '待我审批'
        ? (props.approvalRecords ?? [])
        : (props.approvalHistory ?? []),
);
const selectedId = ref(items.value[0]?.id ?? 0);
const selected = computed(
    () =>
        items.value.find((item) => item.id === selectedId.value) ??
        items.value[0],
);

watch(active, () => {
    selectedId.value = items.value[0]?.id ?? 0;
    comment.value = '';
});

watch(items, (records) => {
    if (!records.some((item) => item.id === selectedId.value)) {
        selectedId.value = records[0]?.id ?? 0;
    }
});

function decide(action: '通过' | '退回') {
    const item = selected.value;

    if (!item) {
        return;
    }

    if (action === '退回' && !comment.value.trim()) {
        toast.warning('请填写退回原因');

        return;
    }

    router.post(
        `/approvals/${item.id}/${action === '通过' ? 'approve' : 'reject'}`,
        { comment: comment.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                comment.value = '';
                toast.success(action === '通过' ? '审批已通过' : '已退回修改', {
                    description:
                        action === '通过'
                            ? item.stage === 'department'
                                ? `${item.title} 已流转至校工会终审。`
                                : `${item.title} 已正式发布。`
                            : '发起人将收到审批意见。',
                });
            },
            onError: () => toast.error('审批处理失败，请检查审批意见'),
        },
    );
}
</script>

<template>
    <Head title="审批中心" />
    <main
        class="paper-grid min-h-full bg-[#f8f7f0] p-4 sm:p-6 lg:p-8 dark:bg-background"
    >
        <div class="mx-auto max-w-[1480px]">
            <PageIntro
                eyebrow="Approval desk"
                title="让每一次出发，都有据可循"
                description="集中处理本单位线路、扩容与关键变更审批。审批意见和版本差异将完整保留。"
            />
            <div class="mt-8 grid gap-5 xl:grid-cols-[.85fr_1.15fr]">
                <section
                    class="overflow-hidden rounded-[24px] border border-[#dfdcd0] bg-[#fffefa] dark:border-border dark:bg-card"
                >
                    <div
                        class="flex items-center justify-between border-b border-[#e6e2d7] p-4 dark:border-border"
                    >
                        <div
                            class="flex gap-1 rounded-full bg-[#eeebe2] p-1 dark:bg-muted"
                        >
                            <button
                                v-for="tab in ['待我审批', '我已审批']"
                                :key="tab"
                                type="button"
                                :class="[
                                    'rounded-full px-4 py-2 text-xs',
                                    active === tab
                                        ? 'bg-white font-semibold text-[#214a3d] shadow-sm dark:bg-card'
                                        : 'text-[#85847b]',
                                ]"
                                @click="active = tab"
                            >
                                {{ tab }}
                            </button>
                        </div>
                        <div class="flex gap-2">
                            <button
                                class="grid size-9 place-items-center rounded-full border border-[#dedacf]"
                            >
                                <Search class="size-3.5" /></button
                            ><button
                                class="grid size-9 place-items-center rounded-full border border-[#dedacf]"
                            >
                                <Filter class="size-3.5" />
                            </button>
                        </div>
                    </div>
                    <div
                        v-if="items.length"
                        class="divide-y divide-[#ebe7dd] dark:divide-border"
                    >
                        <button
                            v-for="item in items"
                            :key="item.id"
                            type="button"
                            :class="[
                                'w-full p-5 text-left transition',
                                selectedId === item.id
                                    ? 'bg-[#f1f3eb]'
                                    : 'hover:bg-[#f8f6ef] dark:hover:bg-muted',
                            ]"
                            @click="selectedId = item.id"
                        >
                            <div class="flex items-start gap-4">
                                <div
                                    :class="[
                                        'grid size-10 shrink-0 place-items-center rounded-xl',
                                        item.type === '扩容审批'
                                            ? 'bg-[#f3e3db] text-[#b35b3c]'
                                            : item.type === '线路变更'
                                              ? 'bg-[#eee9d7] text-[#85692f]'
                                              : 'bg-[#e2eee7] text-[#2f6553]',
                                    ]"
                                >
                                    <component
                                        :is="
                                            item.type === '线路变更'
                                                ? FileDiff
                                                : item.type === '扩容审批'
                                                  ? CircleAlert
                                                  : BadgeCheck
                                        "
                                        class="size-4"
                                    />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div
                                        class="flex items-center justify-between gap-3"
                                    >
                                        <span
                                            class="text-[10px] font-semibold text-[#aa5b3d]"
                                            >{{ item.type }}</span
                                        ><span
                                            class="inline-flex items-center gap-1 text-[10px] text-[#99978e]"
                                            ><Clock3 class="size-3" /> 停留
                                            {{ item.stay }}</span
                                        >
                                    </div>
                                    <p
                                        class="mt-2 truncate text-sm font-semibold text-[#2f4d44] dark:text-foreground"
                                    >
                                        {{ item.title }}
                                    </p>
                                    <p class="mt-1 text-[10px] text-[#96948a]">
                                        {{ item.applicant }} ·
                                        {{ item.department }} · {{ item.time }}
                                    </p>
                                </div>
                                <ChevronRight
                                    class="mt-3 size-4 text-[#aaa89d]"
                                />
                            </div>
                        </button>
                    </div>
                    <div
                        v-else
                        class="grid min-h-72 place-items-center text-center"
                    >
                        <div>
                            <BadgeCheck class="mx-auto size-9 text-[#7a9c8e]" />
                            <p
                                class="font-serif-cn mt-3 text-lg text-[#315348]"
                            >
                                待办已全部处理
                            </p>
                            <p class="mt-1 text-xs text-[#96948a]">
                                辛苦了，去喝杯茶吧
                            </p>
                        </div>
                    </div>
                </section>

                <section
                    v-if="selected"
                    class="rounded-[24px] border border-[#dfdcd0] bg-[#fffefa] p-5 md:p-7 dark:border-border dark:bg-card"
                >
                    <div
                        class="flex flex-wrap items-start justify-between gap-4"
                    >
                        <div>
                            <span
                                class="rounded-full bg-[#e6eee8] px-2.5 py-1 text-[10px] font-semibold text-[#396454]"
                                >{{ selected.type }}</span
                            >
                            <h2
                                class="font-serif-cn mt-4 text-2xl font-semibold text-[#24493d] dark:text-foreground"
                            >
                                {{ selected.title }}
                            </h2>
                            <p class="mt-2 text-xs text-[#8c8b82]">
                                {{ selected.applicant }} 发起 ·
                                {{ selected.department }}
                            </p>
                        </div>
                        <span
                            class="rounded-xl border border-[#e1ddd1] px-3 py-2 text-[10px] text-[#8d8a80]"
                            >当前节点：{{
                                selected.stage === 'union'
                                    ? '校工会终审'
                                    : '二级单位初审'
                            }}</span
                        >
                    </div>
                    <div class="my-6 h-px bg-[#e8e4d9] dark:bg-border" />
                    <div class="grid gap-3 sm:grid-cols-3">
                        <div class="rounded-2xl bg-[#f3f1e8] p-4 dark:bg-muted">
                            <p class="text-[10px] text-[#96948a]">行程天数</p>
                            <p class="font-serif-cn mt-2 text-lg font-semibold">
                                {{ selected.days }} 天
                            </p>
                        </div>
                        <div class="rounded-2xl bg-[#f3f1e8] p-4 dark:bg-muted">
                            <p class="text-[10px] text-[#96948a]">适用人数</p>
                            <p class="font-serif-cn mt-2 text-lg font-semibold">
                                {{ selected.people }}
                            </p>
                        </div>
                        <div class="rounded-2xl bg-[#f3f1e8] p-4 dark:bg-muted">
                            <p class="text-[10px] text-[#96948a]">主要目的地</p>
                            <p class="font-serif-cn mt-2 text-lg font-semibold">
                                {{ selected.location }}
                            </p>
                        </div>
                    </div>
                    <div
                        class="mt-4 grid overflow-hidden rounded-2xl border border-[#e2c78f] bg-[#fff8e9] sm:grid-cols-[210px_1fr]"
                    >
                        <div class="bg-[#a75a3b] p-4 text-white">
                            <p
                                class="flex items-center gap-2 text-[10px] text-white/65"
                            >
                                <CircleDollarSign class="size-4" />经费审核要点
                            </p>
                            <p class="font-serif-cn mt-2 text-lg font-semibold">
                                {{ selected.dailySubsidy }} 元/人/天
                            </p>
                            <p class="mt-1 text-[10px] text-white/60">
                                {{ selected.days }} 天参考补助
                                {{ selected.estimatedSubsidy }} 元/人
                            </p>
                        </div>
                        <div class="p-4">
                            <p
                                class="flex items-center gap-2 text-[10px] font-semibold text-[#75452f]"
                            >
                                <Plane class="size-3.5" />申报的个人自理项目
                            </p>
                            <p class="mt-2 text-xs leading-6 text-[#75695f]">
                                {{ selected.selfFundedItems.join('；') }}
                            </p>
                        </div>
                    </div>
                    <div class="mt-6">
                        <h3 class="text-xs font-semibold text-[#425c53]">
                            线路摘要
                        </h3>
                        <p
                            class="mt-3 rounded-2xl border border-[#e2ded2] p-4 text-xs leading-6 text-[#74776f]"
                        >
                            {{ selected.summary }}
                        </p>
                    </div>
                    <div class="mt-6">
                        <div class="flex items-center justify-between">
                            <h3 class="text-xs font-semibold text-[#425c53]">
                                逐日行程
                            </h3>
                            <Link
                                :href="`/routes/${selected.routeId ?? selected.id}`"
                                class="text-[10px] font-semibold text-[#b45b3d]"
                            >
                                查看完整材料
                            </Link>
                        </div>
                        <ol
                            class="mt-4 space-y-3 border-l border-[#d7d3c6] pl-5"
                        >
                            <li
                                v-for="day in selected.itinerary"
                                :key="day.day"
                                class="relative text-xs text-[#666b64]"
                            >
                                <span
                                    class="absolute -left-[25px] grid size-3 place-items-center rounded-full bg-[#c06140] ring-4 ring-[#fffefa]"
                                /><span
                                    class="font-serif-cn mr-2 font-semibold text-[#2c5346]"
                                    >D{{ day.day }}</span
                                >{{ day.title }}
                            </li>
                        </ol>
                    </div>
                    <label v-if="active === '待我审批'" class="mt-7 block"
                        ><span class="mb-2 block text-xs font-semibold"
                            >审批意见</span
                        ><textarea
                            v-model="comment"
                            rows="3"
                            class="w-full resize-none rounded-2xl border border-[#dcd8cc] bg-[#faf9f3] p-4 text-xs outline-none focus:border-[#53776c] dark:bg-background"
                            placeholder="通过时可选填，退回时必须说明原因"
                        />
                    </label>
                    <div
                        v-if="active === '待我审批'"
                        class="mt-5 flex justify-end gap-3"
                    >
                        <button
                            type="button"
                            class="h-10 rounded-full border border-[#d2cec1] px-5 text-xs font-semibold text-[#7b655d]"
                            @click="decide('退回')"
                        >
                            退回修改</button
                        ><button
                            type="button"
                            class="h-10 rounded-full bg-[#1d4b3e] px-6 text-xs font-semibold text-white"
                            @click="decide('通过')"
                        >
                            通过并流转
                        </button>
                    </div>
                    <div
                        v-else
                        :class="[
                            'mt-7 rounded-2xl border p-4 text-xs',
                            selected.decision === 'approved'
                                ? 'border-[#d5e3da] bg-[#edf4ef] text-[#31594c]'
                                : 'border-[#ead8d0] bg-[#f8efeb] text-[#86513e]',
                        ]"
                    >
                        <p class="font-semibold">
                            {{
                                selected.decision === 'approved'
                                    ? '审批结果：已通过'
                                    : '审批结果：已退回'
                            }}
                        </p>
                        <p v-if="selected.comment" class="mt-2 leading-6">
                            审批意见：{{ selected.comment }}
                        </p>
                    </div>
                </section>
            </div>
        </div>
    </main>
</template>
