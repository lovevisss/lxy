<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowLeft,
    BadgeCheck,
    BellRing,
    CalendarDays,
    Check,
    CircleDollarSign,
    Clock3,
    Download,
    Hotel,
    MapPin,
    PencilLine,
    Plane,
    QrCode,
    Send,
    ShieldCheck,
    Star,
    UserPlus,
    UserRoundCheck,
    UsersRound,
    XCircle,
} from '@lucide/vue';
import { toast } from 'vue-sonner';
import GroupApplicationDialog from '@/components/retreat/GroupApplicationDialog.vue';
import LeaderMemberDialog from '@/components/retreat/LeaderMemberDialog.vue';
import GroupReviewDialog from '@/components/retreat/GroupReviewDialog.vue';
import RouteArtwork from '@/components/retreat/RouteArtwork.vue';
import {
    itineraryByRouteId,
    retreatGroups,
    retreatRoutes,
} from '@/data/retreat';
import type { ItineraryDay, RetreatGroup, RetreatRoute } from '@/data/retreat';

type ApplicationRecord = {
    id: number;
    name: string;
    department: string;
    memberCount: number;
    familyMembers: { name: string; relationship: string }[];
    status: 'pending' | 'approved' | 'rejected';
    contactMobile?: string | null;
    finalConfirmationStatus:
        'not_required' | 'pending' | 'confirmed' | 'declined';
    finalConfirmationAt?: string | null;
    message?: string;
    manuallyAdded?: boolean;
    submittedAt: string;
};

type AvailableMember = {
    id: number;
    name: string;
    department: string;
    email: string;
};

type CurrentReview = {
    id: number;
    route_score: number;
    meal_score: number;
    attraction_score: number;
    accommodation_score: number;
    service_score: number;
    comment?: string | null;
};

type ReviewSummary = {
    count: number;
    overall: number | null;
    route: number | null;
    meal: number | null;
    attraction: number | null;
    accommodation: number | null;
    service: number | null;
};

type ReviewRecord = {
    id: number;
    name: string;
    department: string;
    overallScore: number;
    comment?: string | null;
    reviewedAt: string;
};

const props = defineProps<{
    groupId: number;
    groupRecord?: RetreatGroup;
    routeRecord?: RetreatRoute;
    itineraryRecord?: ItineraryDay[];
    currentApplication?: {
        id: number;
        status: 'pending' | 'approved' | 'rejected';
        member_count: number;
        contact_mobile?: string | null;
        final_confirmation_status:
            'not_required' | 'pending' | 'confirmed' | 'declined';
        final_confirmation_at?: string | null;
    } | null;
    canManage?: boolean;
    isLeader?: boolean;
    leaderApplication?: {
        id: number;
        memberCount: number;
        familyMembers: { name: string; relationship: string }[];
        contactMobile?: string | null;
    } | null;
    availableMembers?: AvailableMember[];
    applications?: ApplicationRecord[];
    canReview?: boolean;
    currentReview?: CurrentReview | null;
    reviewSummary?: ReviewSummary;
    reviewRecords?: ReviewRecord[];
    smsSummary?: {
        total: number;
        pendingProvider: number;
        missingMobile: number;
        sent: number;
        provider: string;
    };
}>();
const group =
    props.groupRecord ??
    retreatGroups.find((item) => item.id === props.groupId) ??
    retreatGroups[0];
const route =
    props.routeRecord ??
    retreatRoutes.find((item) => item.title === group.route) ??
    retreatRoutes[0];
const itinerary = props.itineraryRecord ?? itineraryByRouteId(route.id);
const applicationStatus = ref(props.currentApplication?.status ?? null);
const finalConfirmationStatus = ref(
    props.currentApplication?.final_confirmation_status ?? 'not_required',
);
const lifecycleReason = ref('');
const lifecycleProcessing = ref(false);
const progress = computed(() =>
    Math.min(100, (group.joined / group.capacity) * 100),
);
const reviewDimensions = computed(() => [
    { label: '路线安排', value: props.reviewSummary?.route },
    { label: '团餐安排', value: props.reviewSummary?.meal },
    { label: '景点体验', value: props.reviewSummary?.attraction },
    { label: '住宿体验', value: props.reviewSummary?.accommodation },
    { label: '组织服务', value: props.reviewSummary?.service },
]);

defineOptions({
    layout: {
        breadcrumbs: [
            { title: '组团广场', href: '/groups' },
            { title: '组团详情', href: '#' },
        ],
    },
});

function reviewApplication(id: number, action: 'approved' | 'rejected') {
    router.post(
        `/groups/${group.id}/applications/${id}/review`,
        { action, comment: '' },
        {
            preserveScroll: true,
            onSuccess: () =>
                toast.success(
                    action === 'approved' ? '报名已通过' : '报名已拒绝',
                ),
            onError: () => toast.error('处理失败，请稍后重试'),
        },
    );
}

function changeGroupStatus(action: 'formed' | 'failed' | 'cancelled') {
    if (action !== 'formed' && !lifecycleReason.value.trim()) {
        toast.warning('请先填写原因，短信中会同步告知团员');

        return;
    }

    router.post(
        `/groups/${group.id}/status`,
        { action, reason: lifecycleReason.value },
        {
            preserveScroll: true,
            onStart: () => (lifecycleProcessing.value = true),
            onFinish: () => (lifecycleProcessing.value = false),
            onSuccess: () => {
                lifecycleReason.value = '';
                toast.success(
                    action === 'formed'
                        ? '已成团并生成最终确认短信任务'
                        : action === 'failed'
                          ? '未成团通知任务已生成'
                          : '取消通知任务已生成',
                );
            },
            onError: (errors) =>
                toast.error('状态处理失败', {
                    description: Object.values(errors)[0],
                }),
        },
    );
}

function confirmParticipation(action: 'confirmed' | 'declined') {
    router.post(
        `/groups/${group.id}/final-confirmation`,
        { action },
        {
            preserveScroll: true,
            onSuccess: () => {
                finalConfirmationStatus.value = action;
                toast.success(
                    action === 'confirmed'
                        ? '已完成最终参团确认'
                        : '已记录无法参团',
                );
            },
            onError: (errors) =>
                toast.error('提交失败', {
                    description: Object.values(errors)[0],
                }),
        },
    );
}

function remindFinalConfirmation() {
    router.post(
        `/groups/${group.id}/final-confirmation/remind`,
        {},
        {
            preserveScroll: true,
            onSuccess: () => toast.success('待确认团员的短信提醒任务已生成'),
            onError: (errors) =>
                toast.error('无法发送提醒', {
                    description: Object.values(errors)[0],
                }),
        },
    );
}
</script>

<template>
    <Head :title="group.title" />
    <main
        class="paper-grid min-h-full bg-[#f8f7f0] p-4 sm:p-6 lg:p-8 dark:bg-background"
    >
        <div class="mx-auto max-w-6xl">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <Link
                    href="/groups"
                    class="inline-flex items-center gap-2 text-xs text-[#687169] hover:text-[#b25c3d]"
                    ><ArrowLeft class="size-3.5" /> 返回组团广场</Link
                >
                <div class="flex flex-wrap gap-2">
                    <a
                        v-if="group.attachmentUrl"
                        :href="group.attachmentUrl"
                        class="inline-flex h-9 items-center gap-2 rounded-full border border-[#d3cfc2] bg-[#fffefa] px-4 text-xs font-semibold text-[#45685c]"
                    >
                        <Download class="size-3.5" /> 下载活动 PDF
                    </a>
                    <Link
                        v-if="canManage"
                        :href="`/groups/${group.id}/edit`"
                        class="inline-flex h-9 items-center gap-2 rounded-full bg-[#1d4b3e] px-4 text-xs font-semibold text-white"
                    >
                        <PencilLine class="size-3.5" /> 编辑活动
                    </Link>
                </div>
            </div>
            <section
                class="mt-5 grid overflow-hidden rounded-[30px] border border-[#dfdcd0] bg-[#fffefa] lg:grid-cols-[.76fr_1.24fr] dark:border-border dark:bg-card"
            >
                <RouteArtwork
                    :palette="group.palette"
                    :location="route.location"
                    :accent="route.accent"
                    :image="route.cover"
                    class="min-h-[300px] lg:min-h-[390px]"
                    ><div class="absolute right-6 bottom-6 left-6 text-white">
                        <p
                            class="text-[10px] tracking-[.16em] text-white/60 uppercase"
                        >
                            Group journey
                        </p>
                        <p class="font-serif-cn mt-2 text-2xl font-semibold">
                            {{ route.title }}
                        </p>
                    </div></RouteArtwork
                >
                <div class="flex flex-col p-6 md:p-8">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex flex-wrap gap-2">
                            <span
                                :class="[
                                    'rounded-full px-3 py-1 text-[10px] font-semibold',
                                    group.status === '即将满员'
                                        ? 'bg-[#f6e5dd] text-[#ad5738]'
                                        : group.status === '未成团' ||
                                            group.status === '已取消'
                                          ? 'bg-[#f2e8e4] text-[#94533f]'
                                          : group.status === '已成团'
                                            ? 'bg-[#e7e9ef] text-[#586278]'
                                            : 'bg-[#e7efe8] text-[#386354]',
                                ]"
                                >{{ group.status }}</span
                            >
                            <span
                                class="rounded-full bg-[#f2eee4] px-3 py-1 text-[10px] font-semibold text-[#775c48]"
                            >
                                {{
                                    group.approvalMode === 'automatic'
                                        ? '自动参团'
                                        : '团长审核'
                                }}
                            </span>
                        </div>
                        <span
                            class="text-[10px] font-semibold text-[#a25c40]"
                            >{{ group.deadline }}</span
                        >
                    </div>
                    <h1
                        class="font-serif-cn mt-4 text-3xl font-semibold text-[#1e4438] md:text-4xl dark:text-foreground"
                    >
                        {{ group.title }}
                    </h1>
                    <p class="mt-3 text-sm leading-7 text-[#7b7b73]">
                        本团采用已经校工会审批的“{{
                            route.title
                        }}”线路版本，完整日程见下方。
                    </p>
                    <div class="mt-6 grid gap-3 sm:grid-cols-2">
                        <div
                            class="flex items-center gap-3 rounded-2xl bg-[#f3f1e8] p-4 dark:bg-muted"
                        >
                            <CalendarDays class="size-5 text-[#b55d3e]" />
                            <div>
                                <p class="text-[10px] text-[#929087]">
                                    出行日期
                                </p>
                                <p class="mt-1 text-xs font-semibold">
                                    {{ group.date }}
                                </p>
                            </div>
                        </div>
                        <div
                            class="flex items-center gap-3 rounded-2xl bg-[#f3f1e8] p-4 dark:bg-muted"
                        >
                            <UserRoundCheck class="size-5 text-[#b55d3e]" />
                            <div>
                                <p class="text-[10px] text-[#929087]">团长</p>
                                <p class="mt-1 text-xs font-semibold">
                                    {{ group.leader }} · {{ group.department }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-6">
                        <div class="flex justify-between text-xs">
                            <span class="inline-flex items-center gap-1.5"
                                ><UsersRound class="size-4 text-[#b55d3e]" />
                                已确认 {{ group.joined }} 人</span
                            ><span class="text-[#8e8d84]"
                                >最低 {{ group.min }} · 上限
                                {{ group.capacity }}</span
                            >
                        </div>
                        <div
                            class="mt-3 h-2 overflow-hidden rounded-full bg-[#ebe8dd] dark:bg-background"
                        >
                            <div
                                class="h-full rounded-full bg-gradient-to-r from-[#2d6757] to-[#d6a961]"
                                :style="{ width: `${progress}%` }"
                            />
                        </div>
                    </div>
                    <div
                        v-if="isLeader"
                        class="mt-6 flex h-12 items-center justify-center gap-2 rounded-full bg-[#dfeae4] px-6 text-sm font-semibold text-[#2f5b4c]"
                    >
                        <BadgeCheck class="size-4" />
                        您是团长，已自动加入本团
                        <span
                            v-if="leaderApplication?.familyMembers.length"
                            class="font-normal"
                        >
                            （含 {{ leaderApplication.familyMembers.length }}
                            名家属）
                        </span>
                    </div>
                    <div
                        v-else-if="applicationStatus === 'approved'"
                        class="mt-6 flex h-12 items-center justify-center gap-2 rounded-full bg-[#e6eee8] px-6 text-sm font-semibold text-[#396052]"
                    >
                        <BadgeCheck class="size-4" />
                        参团申请已通过
                    </div>
                    <button
                        v-else-if="applicationStatus === 'pending'"
                        type="button"
                        disabled
                        class="mt-6 inline-flex h-12 items-center justify-center gap-2 rounded-full bg-[#f2eadb] px-6 text-sm font-semibold text-[#8a6634]"
                    >
                        <Clock3 class="size-4" />
                        申请已提交，等待团长审核</button
                    ><GroupApplicationDialog
                        v-else-if="
                            (group.status === '报名中' ||
                                group.status === '即将满员') &&
                            group.joined < group.capacity
                        "
                        :group-id="group.id"
                        :group-title="group.title"
                        :max-participants="group.capacity - group.joined"
                        :daily-subsidy="route.funding?.dailySubsidy"
                        :estimated-subsidy="route.funding?.estimatedSubsidy"
                        :self-funded-items="route.funding?.selfFundedItems"
                        :approval-mode="group.approvalMode"
                        @submitted="applicationStatus = $event"
                    >
                        <button
                            type="button"
                            class="mt-6 inline-flex h-12 w-full items-center justify-center gap-2 rounded-full bg-[#c46140] px-6 text-sm font-semibold text-white transition hover:-translate-y-0.5"
                        >
                            {{
                                applicationStatus === 'rejected'
                                    ? '重新申请参加本团'
                                    : '申请参加本团'
                            }}
                        </button>
                    </GroupApplicationDialog>
                    <div
                        v-else
                        class="mt-6 flex items-center gap-3 rounded-2xl border border-[#dce5df] bg-[#edf3ee] p-4 text-xs text-[#31594c] dark:border-border dark:bg-muted"
                    >
                        <BadgeCheck class="size-5" />
                        本团{{ group.status }}，暂不接受新报名
                    </div>
                </div>
            </section>

            <section
                v-if="group.wechatQrCodeUrl"
                class="mt-6 overflow-hidden rounded-[24px] border border-[#cfe0d6] bg-[#f0f6f2] dark:border-border dark:bg-card"
            >
                <div
                    class="grid items-center gap-6 p-6 sm:grid-cols-[1fr_auto] md:p-8"
                >
                    <div class="flex items-start gap-4">
                        <span
                            class="grid size-11 shrink-0 place-items-center rounded-full bg-[#d9ebe1] text-[#285c49]"
                        >
                            <QrCode class="size-5" />
                        </span>
                        <div>
                            <p
                                class="text-[10px] font-semibold tracking-[.16em] text-[#56806f] uppercase"
                            >
                                Group chat
                            </p>
                            <h2
                                class="font-serif-cn mt-1 text-xl font-semibold text-[#214c3e] dark:text-foreground"
                            >
                                扫码加入本团微信群
                            </h2>
                            <p
                                class="mt-2 max-w-xl text-xs leading-6 text-[#708078]"
                            >
                                使用微信扫描右侧二维码，与团长及同行老师沟通集合、行程和出行准备事项。请勿将二维码转发给非参团人员。
                            </p>
                        </div>
                    </div>
                    <a
                        :href="group.wechatQrCodeUrl"
                        target="_blank"
                        rel="noopener"
                        class="mx-auto block rounded-2xl border border-[#d2ded7] bg-white p-2 shadow-[0_10px_30px_rgba(40,92,73,.10)]"
                        title="打开微信群二维码原图"
                    >
                        <img
                            :src="group.wechatQrCodeUrl"
                            alt="本团微信群二维码"
                            class="size-44 object-contain sm:size-48"
                        />
                    </a>
                </div>
            </section>

            <section
                v-if="route.funding"
                class="mt-6 grid overflow-hidden rounded-[24px] border border-[#e2c78f] bg-[#fff9ed] md:grid-cols-[260px_1fr] dark:border-border dark:bg-card"
            >
                <div
                    class="flex items-center gap-4 bg-[#a75a3b] p-5 text-white md:p-6"
                >
                    <span
                        class="grid size-11 shrink-0 place-items-center rounded-full bg-white/12"
                    >
                        <CircleDollarSign class="size-5 text-[#f3d39a]" />
                    </span>
                    <div>
                        <p class="text-[10px] text-white/60">工会补助标准</p>
                        <p class="font-serif-cn mt-1 text-xl font-semibold">
                            {{ route.funding.dailySubsidy }} 元/人/天
                        </p>
                        <p class="mt-1 text-[10px] text-white/60">
                            本线路参考
                            {{ route.funding.estimatedSubsidy }} 元/人
                        </p>
                    </div>
                </div>
                <div class="p-5 md:p-6">
                    <p
                        class="flex items-center gap-2 text-xs font-semibold text-[#75452f]"
                    >
                        <Plane class="size-4" />需个人自行承担
                    </p>
                    <p class="mt-2 text-xs leading-6 text-[#75695f]">
                        {{ route.funding.selfFundedItems.join('；') }}。
                    </p>
                    <p class="mt-2 text-[10px] leading-5 text-[#9a7865]">
                        {{ route.funding.disclaimer }}
                    </p>
                </div>
            </section>

            <section
                v-if="
                    group.rawStatus === 'formed' &&
                    currentApplication?.status === 'approved' &&
                    !isLeader
                "
                class="contour-lines mt-6 overflow-hidden rounded-[26px] bg-[#173f35] text-white"
            >
                <div
                    class="flex flex-col gap-5 p-6 md:flex-row md:items-center md:justify-between md:p-8"
                >
                    <div class="flex items-start gap-4">
                        <span
                            class="grid size-12 shrink-0 place-items-center rounded-full bg-[#e7b972] text-[#173f35]"
                        >
                            <BellRing class="size-5" />
                        </span>
                        <div>
                            <p
                                class="text-[10px] font-semibold tracking-[.18em] text-[#edbf79] uppercase"
                            >
                                Final confirmation
                            </p>
                            <h2
                                class="font-serif-cn mt-2 text-2xl font-semibold"
                            >
                                请完成最终参团确认
                            </h2>
                            <p class="mt-2 text-xs leading-6 text-white/60">
                                团队已经成团。请在
                                {{
                                    group.finalConfirmationDeadline ?? '出发前'
                                }}
                                确认本人及已登记家属是否按计划参加。
                            </p>
                        </div>
                    </div>
                    <div
                        v-if="finalConfirmationStatus === 'pending'"
                        class="flex shrink-0 flex-wrap gap-2"
                    >
                        <button
                            type="button"
                            class="h-11 rounded-full border border-white/25 px-5 text-xs font-semibold text-white"
                            @click="confirmParticipation('declined')"
                        >
                            无法参加
                        </button>
                        <button
                            type="button"
                            class="h-11 rounded-full bg-[#e7b972] px-6 text-xs font-semibold text-[#173f35]"
                            @click="confirmParticipation('confirmed')"
                        >
                            确认参加
                        </button>
                    </div>
                    <div
                        v-else
                        class="inline-flex h-11 shrink-0 items-center gap-2 rounded-full bg-white/10 px-5 text-xs font-semibold"
                    >
                        <BadgeCheck
                            v-if="finalConfirmationStatus === 'confirmed'"
                            class="size-4 text-[#edbf79]"
                        />
                        <XCircle v-else class="size-4 text-[#e8a184]" />
                        {{
                            finalConfirmationStatus === 'confirmed'
                                ? '已确认参加'
                                : '已登记无法参加'
                        }}
                    </div>
                </div>
            </section>

            <section
                v-if="
                    group.rawStatus === 'failed' ||
                    group.rawStatus === 'cancelled'
                "
                class="mt-6 flex items-start gap-4 rounded-[24px] border border-[#e6c8bc] bg-[#fff4ef] p-6"
            >
                <span
                    class="grid size-11 shrink-0 place-items-center rounded-full bg-[#b95f42] text-white"
                >
                    <XCircle class="size-5" />
                </span>
                <div>
                    <h2
                        class="font-serif-cn text-xl font-semibold text-[#754331]"
                    >
                        {{
                            group.rawStatus === 'failed'
                                ? '本团未能成团'
                                : '本团已由团长取消'
                        }}
                    </h2>
                    <p class="mt-2 text-xs leading-6 text-[#85695d]">
                        {{ group.statusReason || '请联系团长了解具体情况。' }}
                    </p>
                    <p class="mt-2 text-[10px] text-[#a18070]">
                        系统已为已报名人员生成状态提醒短信任务。
                    </p>
                </div>
            </section>

            <section
                v-if="
                    canManage &&
                    ['open', 'formed'].includes(group.rawStatus ?? '')
                "
                class="mt-6 overflow-hidden rounded-[26px] border border-[#d9d5c8] bg-[#fffefa] dark:border-border dark:bg-card"
            >
                <div
                    class="flex flex-col gap-4 border-b border-[#e5e0d4] bg-[#f3f1e8] p-5 sm:flex-row sm:items-center sm:justify-between md:px-8"
                >
                    <div>
                        <p
                            class="text-[10px] font-semibold tracking-[.18em] text-[#b25c3d] uppercase"
                        >
                            Group lifecycle
                        </p>
                        <h2
                            class="font-serif-cn mt-1 text-xl font-semibold text-[#294b40]"
                        >
                            成团与短信通知
                        </h2>
                    </div>
                    <div class="flex gap-2 text-[10px]">
                        <span
                            class="rounded-full bg-white px-3 py-1.5 text-[#546b61]"
                        >
                            已确认 {{ group.joined }} / 最低 {{ group.min }} 人
                        </span>
                        <span
                            class="rounded-full bg-white px-3 py-1.5 text-[#8a6547]"
                        >
                            短信 {{ smsSummary?.total ?? 0 }} 条
                        </span>
                    </div>
                </div>
                <div class="grid gap-6 p-5 md:p-8 lg:grid-cols-[1fr_320px]">
                    <div>
                        <template v-if="group.rawStatus === 'open'">
                            <p class="text-xs leading-6 text-[#737870]">
                                达到最低人数后可手动确认成团；报名截止后系统也会自动判断。成团时会为已审核团员生成登录系统完成最终确认的短信任务。
                            </p>
                            <label class="mt-4 block">
                                <span
                                    class="mb-2 block text-[10px] font-semibold text-[#6f574b]"
                                >
                                    未成团或取消原因
                                </span>
                                <textarea
                                    v-model="lifecycleReason"
                                    rows="3"
                                    maxlength="1000"
                                    class="w-full resize-none rounded-2xl border border-[#d9d4c7] bg-[#faf9f3] p-4 text-sm outline-none focus:border-[#9a6953]"
                                    placeholder="该内容会写入短信，请清楚说明原因"
                                />
                            </label>
                        </template>
                        <template v-else>
                            <p class="text-xs leading-6 text-[#737870]">
                                最终确认截止：{{
                                    group.finalConfirmationDeadline
                                }}。可向尚未确认的团员再次生成短信提醒。
                            </p>
                            <div
                                class="mt-4 grid grid-cols-3 gap-2 text-center"
                            >
                                <div class="rounded-xl bg-[#edf3ee] p-3">
                                    <p
                                        class="text-lg font-semibold text-[#31594c]"
                                    >
                                        {{
                                            group.confirmationCounts
                                                ?.confirmed ?? 0
                                        }}
                                    </p>
                                    <p class="mt-1 text-[10px] text-[#7d857e]">
                                        已确认
                                    </p>
                                </div>
                                <div class="rounded-xl bg-[#fff3df] p-3">
                                    <p
                                        class="text-lg font-semibold text-[#9a6b38]"
                                    >
                                        {{
                                            group.confirmationCounts?.pending ??
                                            0
                                        }}
                                    </p>
                                    <p class="mt-1 text-[10px] text-[#8c806e]">
                                        待确认
                                    </p>
                                </div>
                                <div class="rounded-xl bg-[#f8ebe6] p-3">
                                    <p
                                        class="text-lg font-semibold text-[#a5573d]"
                                    >
                                        {{
                                            group.confirmationCounts
                                                ?.declined ?? 0
                                        }}
                                    </p>
                                    <p class="mt-1 text-[10px] text-[#8c756d]">
                                        无法参加
                                    </p>
                                </div>
                            </div>
                            <label class="mt-4 block">
                                <span
                                    class="mb-2 block text-[10px] font-semibold text-[#6f574b]"
                                >
                                    如需取消，请填写原因
                                </span>
                                <textarea
                                    v-model="lifecycleReason"
                                    rows="2"
                                    maxlength="1000"
                                    class="w-full resize-none rounded-2xl border border-[#d9d4c7] bg-[#faf9f3] p-4 text-sm outline-none focus:border-[#9a6953]"
                                    placeholder="取消原因将通过短信通知团员"
                                />
                            </label>
                        </template>
                    </div>
                    <div class="flex flex-col justify-center gap-2">
                        <button
                            v-if="group.rawStatus === 'open'"
                            type="button"
                            :disabled="
                                lifecycleProcessing || group.joined < group.min
                            "
                            class="inline-flex h-11 items-center justify-center gap-2 rounded-full bg-[#1d4b3e] px-5 text-xs font-semibold text-white disabled:cursor-not-allowed disabled:opacity-40"
                            @click="changeGroupStatus('formed')"
                        >
                            <BadgeCheck class="size-4" />确认成团并通知
                        </button>
                        <button
                            v-if="group.rawStatus === 'open'"
                            type="button"
                            :disabled="lifecycleProcessing"
                            class="h-11 rounded-full border border-[#d8b596] px-5 text-xs font-semibold text-[#8b593e]"
                            @click="changeGroupStatus('failed')"
                        >
                            标记未成团并通知
                        </button>
                        <button
                            v-if="group.rawStatus === 'formed'"
                            type="button"
                            class="inline-flex h-11 items-center justify-center gap-2 rounded-full bg-[#1d4b3e] px-5 text-xs font-semibold text-white"
                            @click="remindFinalConfirmation"
                        >
                            <Send class="size-4" />再次提醒待确认团员
                        </button>
                        <button
                            type="button"
                            :disabled="lifecycleProcessing"
                            class="h-11 rounded-full border border-[#dfb7aa] px-5 text-xs font-semibold text-[#a3573f]"
                            @click="changeGroupStatus('cancelled')"
                        >
                            取消本团并通知
                        </button>
                        <p
                            class="mt-2 text-center text-[10px] leading-5 text-[#918d84]"
                        >
                            {{
                                smsSummary?.provider ?? '中国移动接口待接入'
                            }}；当前先记录待发送任务与手机号。
                        </p>
                    </div>
                </div>
            </section>

            <section
                v-if="canManage"
                class="mt-6 rounded-[26px] border border-[#dfdcd0] bg-[#fffefa] p-5 md:p-8 dark:border-border dark:bg-card"
            >
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <p
                            class="text-[10px] font-semibold tracking-[.2em] text-[#b25c3d] uppercase"
                        >
                            Leader workspace
                        </p>
                        <h2
                            class="font-serif-cn mt-2 text-2xl font-semibold text-[#244a3d]"
                        >
                            {{
                                group.approvalMode === 'automatic'
                                    ? '自动参团成员'
                                    : '团员申请审核'
                            }}
                        </h2>
                    </div>
                    <span class="text-xs text-[#8d8c83]"
                        >共 {{ applications?.length ?? 0 }} 条申请</span
                    >
                </div>
                <div
                    v-if="group.rawStatus === 'open'"
                    class="mt-5 flex flex-col gap-3 rounded-2xl border border-[#dfe4de] bg-[#f4f7f3] p-4 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div>
                        <p class="text-xs font-semibold text-[#315348]">
                            团长成员管理
                        </p>
                        <p class="mt-1 text-[10px] leading-5 text-[#7f847e]">
                            可直接添加已注册教职工，也可维护团长本人的随行家属。
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <LeaderMemberDialog
                            :group-id="group.id"
                            mode="member"
                            :available-members="availableMembers"
                            :remaining-capacity="group.capacity - group.joined"
                        >
                            <button
                                type="button"
                                :disabled="group.joined >= group.capacity"
                                class="inline-flex h-10 items-center gap-2 rounded-full bg-[#1d4b3e] px-5 text-xs font-semibold text-white transition hover:-translate-y-0.5 disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:translate-y-0"
                            >
                                <UserPlus class="size-4" />添加团员
                            </button>
                        </LeaderMemberDialog>
                        <LeaderMemberDialog
                            v-if="isLeader"
                            :group-id="group.id"
                            mode="leader-family"
                            :family-members="leaderApplication?.familyMembers"
                            :remaining-capacity="group.capacity - group.joined"
                        >
                            <button
                                type="button"
                                class="inline-flex h-10 items-center gap-2 rounded-full border border-[#bfcfc6] bg-white px-5 text-xs font-semibold text-[#31594c] transition hover:bg-[#eaf0ec]"
                            >
                                <UsersRound class="size-4" />我的随行家属
                            </button>
                        </LeaderMemberDialog>
                    </div>
                </div>
                <div
                    v-if="applications?.length"
                    class="mt-6 divide-y divide-[#e7e3d8] rounded-2xl border border-[#e2ded2]"
                >
                    <article
                        v-for="application in applications"
                        :key="application.id"
                        class="flex flex-col gap-4 p-4 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div>
                            <div class="flex items-center gap-2">
                                <p class="text-sm font-semibold text-[#2f5046]">
                                    {{ application.name }}
                                </p>
                                <span
                                    class="rounded-full bg-[#f0eee6] px-2 py-0.5 text-[10px] text-[#77786f]"
                                    >{{ application.department }}</span
                                >
                                <span
                                    v-if="application.manuallyAdded"
                                    class="rounded-full bg-[#e5eee8] px-2 py-0.5 text-[10px] font-semibold text-[#396052]"
                                >
                                    团长添加
                                </span>
                            </div>
                            <p class="mt-2 text-xs text-[#85847c]">
                                {{ application.memberCount }} 人 ·
                                {{ application.submittedAt }}
                                <template v-if="application.contactMobile">
                                    · {{ application.contactMobile }}
                                </template>
                                <template v-if="application.message">
                                    · {{ application.message }}
                                </template>
                            </p>
                            <p
                                v-if="
                                    group.rawStatus === 'formed' &&
                                    application.status === 'approved'
                                "
                                class="mt-2 text-[10px] font-semibold"
                                :class="
                                    application.finalConfirmationStatus ===
                                    'confirmed'
                                        ? 'text-[#39705d]'
                                        : application.finalConfirmationStatus ===
                                            'declined'
                                          ? 'text-[#a5573d]'
                                          : 'text-[#9a6b38]'
                                "
                            >
                                最终确认：{{
                                    application.finalConfirmationStatus ===
                                    'confirmed'
                                        ? '确认参加'
                                        : application.finalConfirmationStatus ===
                                            'declined'
                                          ? '无法参加'
                                          : '等待团员确认'
                                }}
                            </p>
                            <div
                                v-if="application.familyMembers.length"
                                class="mt-2 flex flex-wrap gap-1.5"
                            >
                                <span
                                    v-for="member in application.familyMembers"
                                    :key="`${member.name}-${member.relationship}`"
                                    class="rounded-full bg-[#f0eee6] px-2.5 py-1 text-[10px] text-[#666b64]"
                                >
                                    家属：{{ member.name }}（{{
                                        member.relationship
                                    }}）
                                </span>
                            </div>
                        </div>
                        <div
                            v-if="application.status === 'pending'"
                            class="flex gap-2"
                        >
                            <button
                                type="button"
                                class="h-9 rounded-full border border-[#d8d3c7] px-4 text-xs text-[#765f57]"
                                @click="
                                    reviewApplication(
                                        application.id,
                                        'rejected',
                                    )
                                "
                            >
                                拒绝
                            </button>
                            <button
                                type="button"
                                class="h-9 rounded-full bg-[#1d4b3e] px-5 text-xs font-semibold text-white"
                                @click="
                                    reviewApplication(
                                        application.id,
                                        'approved',
                                    )
                                "
                            >
                                通过
                            </button>
                        </div>
                        <span
                            v-else
                            :class="[
                                'rounded-full px-3 py-1.5 text-xs font-semibold',
                                application.status === 'approved'
                                    ? 'bg-[#e6eee8] text-[#396052]'
                                    : 'bg-[#f2e8e4] text-[#94533f]',
                            ]"
                        >
                            {{
                                application.status === 'approved'
                                    ? '已通过'
                                    : '已拒绝'
                            }}
                        </span>
                    </article>
                </div>
                <div
                    v-else
                    class="mt-6 rounded-2xl bg-[#f3f1e8] p-8 text-center text-xs text-[#8b8a82]"
                >
                    当前暂无待处理或历史报名申请
                </div>
            </section>

            <section
                v-if="group.status === '已结束'"
                class="contour-lines mt-8 flex flex-col gap-5 rounded-[26px] bg-[#173f35] p-6 text-white sm:flex-row sm:items-center sm:justify-between md:p-8"
            >
                <div>
                    <p
                        class="text-[10px] font-semibold tracking-[.2em] text-[#edbf79] uppercase"
                    >
                        Route reputation
                    </p>
                    <h2 class="font-serif-cn mt-2 text-2xl font-semibold">
                        评价已归入线路口碑
                    </h2>
                    <p class="mt-2 text-xs leading-6 text-white/55">
                        本团仅用于核验实际参团资格；评分与体验反馈统一展示在“{{
                            route.title
                        }}”线路详情中。
                    </p>
                </div>
                <Link
                    :href="`/routes/${route.id}#route-reviews`"
                    class="inline-flex h-11 shrink-0 items-center justify-center gap-2 rounded-full bg-[#e6b76f] px-6 text-sm font-semibold text-[#173e35]"
                >
                    <Star class="size-4" />
                    {{ canReview ? '前往评价本路线' : '查看线路评价' }}
                </Link>
            </section>

            <section
                v-if="false"
                class="mt-8 overflow-hidden rounded-[26px] border border-[#dfdcd0] bg-[#fffefa] dark:border-border dark:bg-card"
            >
                <div
                    class="contour-lines flex flex-col gap-5 bg-[#173f35] p-6 text-white sm:flex-row sm:items-end sm:justify-between md:p-8"
                >
                    <div>
                        <p
                            class="text-[10px] font-semibold tracking-[.2em] text-[#edbf79] uppercase"
                        >
                            Journey voices
                        </p>
                        <h2 class="font-serif-cn mt-2 text-2xl font-semibold">
                            团员旅行体验评价
                        </h2>
                        <p class="mt-2 text-xs leading-6 text-white/55">
                            行程结束后，由实际参团成员对路线、团餐、景点、住宿及组织服务进行评价。
                        </p>
                    </div>
                    <GroupReviewDialog
                        v-if="canReview"
                        :route-id="route.id"
                        :source-group-id="group.id"
                        :route-title="route.title"
                        :current-review="currentReview"
                    >
                        <button
                            type="button"
                            class="inline-flex h-11 shrink-0 items-center justify-center gap-2 rounded-full bg-[#e6b76f] px-6 text-sm font-semibold text-[#173e35]"
                        >
                            <Star class="size-4" />
                            {{
                                currentReview ? '修改我的评价' : '评价本次行程'
                            }}
                        </button>
                    </GroupReviewDialog>
                </div>

                <div class="p-5 md:p-8">
                    <div
                        v-if="(reviewSummary?.count ?? 0) > 0"
                        class="grid gap-4 lg:grid-cols-[220px_1fr]"
                    >
                        <div
                            class="flex flex-col items-center justify-center rounded-2xl bg-[#f1eee4] p-6 text-center dark:bg-muted"
                        >
                            <div class="flex items-end gap-1">
                                <span
                                    class="font-serif-cn text-5xl font-semibold text-[#b45d3d]"
                                    >{{
                                        reviewSummary?.overall?.toFixed(1)
                                    }}</span
                                >
                                <span class="pb-1 text-sm text-[#8b887e]"
                                    >/ 5</span
                                >
                            </div>
                            <div class="mt-3 flex gap-1">
                                <Star
                                    v-for="score in 5"
                                    :key="score"
                                    :class="[
                                        'size-4',
                                        score <=
                                        Math.round(reviewSummary?.overall ?? 0)
                                            ? 'fill-[#d99a45] text-[#d99a45]'
                                            : 'text-[#cbc5b8]',
                                    ]"
                                />
                            </div>
                            <p class="mt-3 text-[10px] text-[#89877d]">
                                来自 {{ reviewSummary?.count }} 位团员
                            </p>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
                            <div
                                v-for="dimension in reviewDimensions"
                                :key="dimension.label"
                                class="rounded-2xl border border-[#e2ded2] p-4"
                            >
                                <p class="text-[10px] text-[#8f8d84]">
                                    {{ dimension.label }}
                                </p>
                                <p
                                    class="font-serif-cn mt-2 text-2xl font-semibold text-[#315348]"
                                >
                                    {{ dimension.value?.toFixed(1) }}
                                </p>
                                <div
                                    class="mt-2 h-1.5 rounded-full bg-[#ece8dc]"
                                >
                                    <div
                                        class="h-full rounded-full bg-[#c66a47]"
                                        :style="{
                                            width: `${((dimension.value ?? 0) / 5) * 100}%`,
                                        }"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        v-if="reviewRecords?.some((review) => review.comment)"
                        class="mt-7 grid gap-3 md:grid-cols-2"
                    >
                        <article
                            v-for="review in reviewRecords?.filter(
                                (item) => item.comment,
                            )"
                            :key="review.id"
                            class="rounded-2xl bg-[#f7f4eb] p-5 dark:bg-muted"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p
                                        class="text-sm font-semibold text-[#315348]"
                                    >
                                        {{ review.name }}
                                    </p>
                                    <p class="mt-1 text-[10px] text-[#96948a]">
                                        {{ review.department }} ·
                                        {{ review.reviewedAt }}
                                    </p>
                                </div>
                                <span
                                    class="inline-flex items-center gap-1 rounded-full bg-white px-2.5 py-1 text-xs font-semibold text-[#b45d3d]"
                                    ><Star
                                        class="size-3 fill-[#d99a45] text-[#d99a45]"
                                    />{{ review.overallScore }}</span
                                >
                            </div>
                            <p class="mt-4 text-xs leading-6 text-[#6f746d]">
                                {{ review.comment }}
                            </p>
                        </article>
                    </div>

                    <div
                        v-if="(reviewSummary?.count ?? 0) === 0"
                        class="rounded-2xl border border-dashed border-[#d8d3c6] p-8 text-center"
                    >
                        <Star class="mx-auto size-7 text-[#c18a4d]" />
                        <p class="font-serif-cn mt-3 text-lg text-[#315348]">
                            还没有团员评价
                        </p>
                        <p class="mt-1 text-xs text-[#8e8b82]">
                            已确认参团的成员现在可以分享本次旅行体验。
                        </p>
                    </div>
                </div>
            </section>

            <section class="mt-8 grid gap-6 lg:grid-cols-[1fr_300px]">
                <div
                    class="rounded-[26px] border border-[#dfdcd0] bg-[#fffefa] p-5 md:p-8 dark:border-border dark:bg-card"
                >
                    <div>
                        <p
                            class="text-[10px] font-semibold tracking-[.2em] text-[#b25c3d] uppercase"
                        >
                            Group itinerary
                        </p>
                        <h2
                            class="font-serif-cn mt-2 text-2xl font-semibold text-[#244a3d]"
                        >
                            本团完整日程
                        </h2>
                        <p class="mt-2 text-xs text-[#8d8c83]">
                            以下日程来自审批通过的线路版本，团长发布的集合信息将另行通知。
                        </p>
                    </div>
                    <ol class="mt-8">
                        <li
                            v-for="day in itinerary"
                            :key="day.day"
                            class="relative grid gap-4 pb-8 pl-14 last:pb-0 sm:grid-cols-[110px_1fr]"
                        >
                            <div
                                class="absolute top-0 bottom-0 left-[19px] w-px bg-[#d9d5c8] last:hidden"
                            />
                            <div
                                class="font-serif-cn absolute top-0 left-0 grid size-10 place-items-center rounded-full bg-[#1d4b3e] text-sm font-semibold text-white ring-8 ring-[#fffefa] dark:ring-card"
                            >
                                {{ day.day }}
                            </div>
                            <div>
                                <p
                                    class="text-[10px] tracking-[.12em] text-[#a05d43] uppercase"
                                >
                                    Day {{ day.day }}
                                </p>
                                <p class="mt-1 text-xs text-[#777970]">
                                    {{ day.period }}
                                </p>
                            </div>
                            <article
                                class="rounded-2xl bg-[#f4f2e9] p-4 md:p-5 dark:bg-muted"
                            >
                                <div
                                    class="flex flex-wrap items-start justify-between gap-2"
                                >
                                    <h3
                                        class="font-serif-cn text-lg font-semibold text-[#2c4e43] dark:text-foreground"
                                    >
                                        {{ day.title }}
                                    </h3>
                                    <span
                                        class="inline-flex items-center gap-1 text-[10px] text-[#8b8a81]"
                                        ><MapPin class="size-3" />{{
                                            day.location
                                        }}</span
                                    >
                                </div>
                                <p
                                    class="mt-2 text-xs leading-6 text-[#73776f]"
                                >
                                    {{ day.plan }}
                                </p>
                                <div
                                    class="mt-4 flex flex-wrap gap-4 border-t border-[#e2ded2] pt-3 text-[10px] text-[#8c8a80] dark:border-border"
                                >
                                    <span
                                        class="inline-flex items-center gap-1.5"
                                        ><Hotel
                                            class="size-3.5 text-[#aa5b3d]"
                                        />{{ day.stay }}</span
                                    ><span
                                        v-if="day.note"
                                        class="inline-flex items-center gap-1.5"
                                        ><ShieldCheck
                                            class="size-3.5 text-[#aa5b3d]"
                                        />{{ day.note }}</span
                                    >
                                </div>
                            </article>
                        </li>
                    </ol>
                </div>
                <aside class="space-y-4 lg:sticky lg:top-6 lg:self-start">
                    <div
                        v-if="group.meetingInfo || group.notes"
                        class="rounded-2xl border border-[#dfdcd0] bg-[#fffefa] p-5 dark:border-border dark:bg-card"
                    >
                        <h3 class="text-sm font-semibold text-[#315348]">
                            团长发布信息
                        </h3>
                        <div v-if="group.meetingInfo" class="mt-4">
                            <p class="text-[10px] font-semibold text-[#a45d42]">
                                集合安排
                            </p>
                            <p
                                class="mt-1 text-[11px] leading-5 text-[#747870]"
                            >
                                {{ group.meetingInfo }}
                            </p>
                        </div>
                        <div v-if="group.notes" class="mt-4">
                            <p class="text-[10px] font-semibold text-[#a45d42]">
                                补充说明
                            </p>
                            <p
                                class="mt-1 text-[11px] leading-5 text-[#747870]"
                            >
                                {{ group.notes }}
                            </p>
                        </div>
                    </div>
                    <div
                        class="rounded-2xl border border-[#dfdcd0] bg-[#fffefa] p-5 dark:border-border dark:bg-card"
                    >
                        <h3 class="text-sm font-semibold text-[#315348]">
                            报名说明
                        </h3>
                        <ul
                            class="mt-4 space-y-3 text-[11px] leading-5 text-[#747870]"
                        >
                            <li class="flex gap-2">
                                <Check
                                    class="mt-0.5 size-3.5 shrink-0 text-[#34705b]"
                                />支持教职工本人及家属同行
                            </li>
                            <li class="flex gap-2">
                                <Check
                                    class="mt-0.5 size-3.5 shrink-0 text-[#34705b]"
                                />{{
                                    group.approvalMode === 'automatic'
                                        ? '名额充足时提交即自动确认参团'
                                        : '提交后由团长审核确认'
                                }}
                            </li>
                            <li class="flex gap-2">
                                <Check
                                    class="mt-0.5 size-3.5 shrink-0 text-[#34705b]"
                                />人数达到上限后自动关闭报名
                            </li>
                        </ul>
                    </div>
                    <div class="rounded-2xl bg-[#1c493d] p-5 text-white">
                        <Clock3 class="size-5 text-[#e4b872]" />
                        <p class="font-serif-cn mt-4 text-lg font-semibold">
                            报名截止提醒
                        </p>
                        <p class="mt-2 text-xs leading-6 text-white/55">
                            {{ group.deadline }}，{{
                                group.approvalMode === 'automatic'
                                    ? '报名成功后立即计入已确认人数。'
                                    : '团长将在提交后 48 小时内完成审核。'
                            }}
                        </p>
                    </div>
                </aside>
            </section>
        </div>
    </main>
</template>
