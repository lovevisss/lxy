<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import {
    ArrowLeft,
    ArrowRight,
    BadgeCheck,
    CircleDollarSign,
    Clock3,
    FileText,
    Heart,
    Hotel,
    MapPin,
    Plane,
    ShieldCheck,
    Star,
    UsersRound,
} from '@lucide/vue';
import GroupReviewDialog from '@/components/retreat/GroupReviewDialog.vue';
import RouteArtwork from '@/components/retreat/RouteArtwork.vue';
import { itineraryByRouteId, routeById } from '@/data/retreat';
import type { ItineraryDay, RetreatRoute } from '@/data/retreat';

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

type RecommendedGroup = {
    id: number;
    title: string;
    routeTitle: string;
    location: string;
    days: number;
    leader: string;
    date: string;
    joined: number;
    capacity: number;
    remaining: number;
    sameRoute: boolean;
    routeRating: number | null;
    reviewCount: number;
};

const props = defineProps<{
    routeId: number;
    routeRecord?: RetreatRoute;
    itineraryRecord?: ItineraryDay[];
    reviewableGroup?: { id: number; title: string; returnDate: string } | null;
    currentReview?: CurrentReview | null;
    reviewSummary?: ReviewSummary;
    reviewRecords?: ReviewRecord[];
    recommendedGroups?: RecommendedGroup[];
}>();
const route = props.routeRecord ?? routeById(props.routeId);
const itinerary = props.itineraryRecord ?? itineraryByRouteId(props.routeId);
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
            { title: '线路库', href: '/routes' },
            { title: '线路详情', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="route.title" />
    <main
        class="paper-grid min-h-full bg-[#f8f7f0] p-4 sm:p-6 lg:p-8 dark:bg-background"
    >
        <div class="mx-auto max-w-6xl">
            <Link
                href="/routes"
                class="inline-flex items-center gap-2 text-xs text-[#687169] hover:text-[#b25c3d]"
                ><ArrowLeft class="size-3.5" /> 返回线路库</Link
            >

            <section
                class="mt-5 grid overflow-hidden rounded-[30px] border border-[#dfdcd0] bg-[#fffefa] shadow-[0_22px_65px_rgba(40,66,56,.09)] lg:grid-cols-[.88fr_1.12fr] dark:border-border dark:bg-card"
            >
                <RouteArtwork
                    :palette="route.palette"
                    :location="route.location"
                    :accent="route.accent"
                    :image="route.cover"
                    class="min-h-[320px] lg:min-h-[430px]"
                >
                    <div class="absolute right-6 bottom-6 left-6">
                        <p
                            class="text-[10px] tracking-[0.18em] text-white/60 uppercase"
                        >
                            Approved route · V2
                        </p>
                        <p
                            class="font-serif-cn mt-2 text-2xl font-semibold text-white"
                        >
                            山水有期，从容出发
                        </p>
                    </div>
                </RouteArtwork>
                <div class="flex flex-col p-6 md:p-9">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex flex-wrap gap-2">
                            <span
                                v-for="tag in route.tags"
                                :key="tag"
                                class="rounded-full bg-[#f1eee3] px-3 py-1 text-[10px] text-[#696b63] dark:bg-muted"
                                >{{ tag }}</span
                            >
                        </div>
                        <button
                            type="button"
                            class="grid size-9 place-items-center rounded-full border border-[#ddd9cc] text-[#b25c3d]"
                            aria-label="收藏线路"
                        >
                            <Heart class="size-4" />
                        </button>
                    </div>
                    <h1
                        class="font-serif-cn mt-5 text-3xl leading-tight font-semibold text-[#1e4438] md:text-4xl dark:text-foreground"
                    >
                        {{ route.title }}
                    </h1>
                    <p class="mt-4 text-sm leading-7 text-[#747870]">
                        {{ route.summary }}
                    </p>
                    <div
                        v-if="route.highlights?.length"
                        class="mt-5 flex flex-wrap gap-2"
                    >
                        <span
                            v-for="highlight in route.highlights"
                            :key="highlight"
                            class="rounded-full border border-[#ded9ca] px-3 py-1.5 text-[10px] text-[#5f6962]"
                            >{{ highlight }}</span
                        >
                    </div>
                    <div class="mt-6 grid grid-cols-2 gap-3">
                        <div class="rounded-2xl bg-[#f2f0e7] p-4 dark:bg-muted">
                            <Clock3 class="size-4 text-[#b65f40]" />
                            <p class="mt-3 text-[10px] text-[#939188]">
                                建议行程
                            </p>
                            <p class="mt-1 text-sm font-semibold">
                                {{ route.days }} 天 {{ route.days - 1 }} 晚
                            </p>
                        </div>
                        <div class="rounded-2xl bg-[#f2f0e7] p-4 dark:bg-muted">
                            <UsersRound class="size-4 text-[#b65f40]" />
                            <p class="mt-3 text-[10px] text-[#939188]">
                                适用人数
                            </p>
                            <p class="mt-1 text-sm font-semibold">
                                {{ route.people }}
                            </p>
                        </div>
                    </div>
                    <div
                        class="mt-6 flex items-center gap-3 rounded-2xl border border-[#dce5df] bg-[#edf3ee] p-4 dark:border-border dark:bg-muted"
                    >
                        <BadgeCheck class="size-5 shrink-0 text-[#2c6552]" />
                        <div>
                            <p class="text-xs font-semibold text-[#31594c]">
                                {{
                                    route.importedByUnion
                                        ? '校工会统一导入'
                                        : '校工会终审通过'
                                }}
                            </p>
                            <p class="mt-1 text-[10px] text-[#7d857e]">
                                当前版本可直接创建组团，无需重复审批
                            </p>
                        </div>
                    </div>
                    <Link
                        :href="`/groups/create?route=${route.id}`"
                        class="mt-6 inline-flex h-12 items-center justify-center gap-2 rounded-full bg-[#c46140] px-6 text-sm font-semibold text-white shadow-[0_12px_26px_rgba(177,82,52,.2)] transition hover:-translate-y-0.5 hover:bg-[#b55536]"
                        >使用这条线路发起组团 <ArrowRight class="size-4"
                    /></Link>
                </div>
            </section>

            <section
                v-if="route.funding"
                class="mt-6 overflow-hidden rounded-[26px] border border-[#e2c78f] bg-[#fff9ed] dark:border-border dark:bg-card"
            >
                <div class="grid lg:grid-cols-[.8fr_1.2fr]">
                    <div
                        class="contour-lines bg-[#a75a3b] p-6 text-white md:p-8"
                    >
                        <div class="flex items-center gap-3">
                            <span
                                class="grid size-11 place-items-center rounded-full bg-white/12"
                            >
                                <CircleDollarSign
                                    class="size-5 text-[#f3d39a]"
                                />
                            </span>
                            <div>
                                <p
                                    class="text-[10px] font-semibold tracking-[.18em] text-[#f3d39a] uppercase"
                                >
                                    Union subsidy
                                </p>
                                <h2
                                    class="font-serif-cn mt-1 text-xl font-semibold"
                                >
                                    工会疗休养经费
                                </h2>
                            </div>
                        </div>
                        <p class="font-serif-cn mt-6 text-4xl font-semibold">
                            {{ route.funding.dailySubsidy }} 元
                            <span class="text-sm font-normal text-white/65"
                                >/ 人 / 天</span
                            >
                        </p>
                        <p class="mt-3 text-xs leading-6 text-white/65">
                            本线路 {{ route.days }} 天，工会补助参考总额为每人
                            <strong class="text-[#f5d69e]">
                                {{ route.funding.estimatedSubsidy }} 元
                            </strong>
                            。
                        </p>
                    </div>
                    <div class="p-6 md:p-8">
                        <div class="flex items-center gap-2 text-[#75452f]">
                            <Plane class="size-4" />
                            <h3 class="text-sm font-semibold">
                                以下费用需个人自理
                            </h3>
                        </div>
                        <ul class="mt-4 grid gap-2 sm:grid-cols-2">
                            <li
                                v-for="item in route.funding.selfFundedItems"
                                :key="item"
                                class="rounded-xl border border-[#ead7b2] bg-white/60 px-4 py-3 text-xs leading-5 text-[#75695f]"
                            >
                                {{ item }}
                            </li>
                        </ul>
                        <p
                            class="mt-4 border-t border-[#ead8b7] pt-4 text-[10px] leading-5 text-[#98745f]"
                        >
                            {{
                                route.funding.disclaimer
                            }}具体出票、支付和结算方式以团长通知为准。
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
                            class="text-[10px] font-semibold tracking-[0.2em] text-[#b25c3d] uppercase"
                        >
                            Daily itinerary
                        </p>
                        <h2
                            class="font-serif-cn mt-2 text-2xl font-semibold text-[#244a3d]"
                        >
                            逐日行程安排
                        </h2>
                        <p class="mt-2 text-xs text-[#8d8c83]">
                            创建组团时将以此版本为基础，关键行程变更需重新审批。
                        </p>
                    </div>
                    <ol class="mt-8 space-y-0">
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
                                    Day {{ day.day }} · {{ day.period }}
                                </p>
                                <p class="mt-1 text-xs text-[#777970]">
                                    {{ day.location }}
                                </p>
                            </div>
                            <article
                                class="rounded-2xl bg-[#f4f2e9] p-4 md:p-5 dark:bg-muted"
                            >
                                <h3
                                    class="font-serif-cn text-lg font-semibold text-[#2c4e43] dark:text-foreground"
                                >
                                    {{ day.title }}
                                </h3>
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
                        class="rounded-2xl border border-[#dfdcd0] bg-[#fffefa] p-5 dark:border-border dark:bg-card"
                    >
                        <h3 class="text-sm font-semibold text-[#315348]">
                            线路资料
                        </h3>
                        <button
                            type="button"
                            class="mt-4 flex w-full items-center gap-3 rounded-xl bg-[#f3f1e8] p-3 text-left dark:bg-muted"
                        >
                            <span
                                class="grid size-9 place-items-center rounded-lg bg-white text-[#b15d3d] shadow-sm dark:bg-card"
                                ><FileText class="size-4" /></span
                            ><span class="min-w-0"
                                ><span
                                    class="block truncate text-xs font-semibold"
                                    >{{ route.title }}行程方案.pdf</span
                                ><span
                                    class="mt-1 block text-[10px] text-[#98968d]"
                                    >2.4 MB · 已安全扫描</span
                                ></span
                            >
                        </button>
                    </div>
                    <div class="rounded-2xl bg-[#1c493d] p-5 text-white">
                        <MapPin class="size-5 text-[#e4b872]" />
                        <p class="font-serif-cn mt-4 text-lg font-semibold">
                            准备好出发了吗？
                        </p>
                        <p class="mt-2 text-xs leading-6 text-white/55">
                            创建团期后即可邀请同事和家属报名。
                        </p>
                        <Link
                            :href="`/groups/create?route=${route.id}`"
                            class="mt-5 inline-flex items-center gap-2 text-xs font-semibold text-[#edc789]"
                            >发起组团 <ArrowRight class="size-3.5"
                        /></Link>
                    </div>
                </aside>
            </section>

            <section
                v-if="route.service"
                class="mt-6 rounded-[26px] border border-[#dfdcd0] bg-[#fffefa] p-5 md:p-8 dark:border-border dark:bg-card"
            >
                <div>
                    <p
                        class="text-[10px] font-semibold tracking-[0.2em] text-[#b25c3d] uppercase"
                    >
                        Service standards
                    </p>
                    <h2
                        class="font-serif-cn mt-2 text-2xl font-semibold text-[#244a3d]"
                    >
                        接待与服务标准
                    </h2>
                </div>
                <div class="mt-6 grid gap-3 md:grid-cols-2">
                    <div
                        v-for="item in [
                            ['去程交通', route.service.inboundTransport],
                            ['返程交通', route.service.outboundTransport],
                            ['住宿标准', route.service.hotels],
                            ['用餐安排', route.service.meals],
                            ['当地交通', route.service.localTransport],
                            ['门票范围', route.service.tickets],
                            ['导游服务', route.service.guide],
                            ['保险保障', route.service.insurance],
                        ]"
                        :key="item[0]"
                        class="rounded-2xl bg-[#f4f2e9] p-4 dark:bg-muted"
                    >
                        <p class="text-[10px] font-semibold text-[#a35c42]">
                            {{ item[0] }}
                        </p>
                        <p class="mt-2 text-xs leading-6 text-[#686e67]">
                            {{ item[1] }}
                        </p>
                    </div>
                </div>
                <div class="mt-5 grid gap-5 lg:grid-cols-2">
                    <div class="rounded-2xl border border-[#e1ddd1] p-5">
                        <h3 class="text-xs font-semibold text-[#315348]">
                            增值服务
                        </h3>
                        <ul class="mt-3 space-y-2">
                            <li
                                v-for="extra in route.service.extras"
                                :key="extra"
                                class="flex gap-2 text-[11px] leading-5 text-[#737870]"
                            >
                                <BadgeCheck
                                    class="mt-0.5 size-3.5 shrink-0 text-[#39705d]"
                                />{{ extra }}
                            </li>
                        </ul>
                    </div>
                    <div class="rounded-2xl border border-[#e1ddd1] p-5">
                        <h3 class="text-xs font-semibold text-[#315348]">
                            注意事项
                        </h3>
                        <ul class="mt-3 space-y-2">
                            <li
                                v-for="notice in route.service.notices"
                                :key="notice"
                                class="flex gap-2 text-[11px] leading-5 text-[#737870]"
                            >
                                <ShieldCheck
                                    class="mt-0.5 size-3.5 shrink-0 text-[#b25c3d]"
                                />{{ notice }}
                            </li>
                        </ul>
                    </div>
                </div>
            </section>

            <section
                id="route-reviews"
                class="mt-6 scroll-mt-6 overflow-hidden rounded-[26px] border border-[#dfdcd0] bg-[#fffefa] dark:border-border dark:bg-card"
            >
                <div
                    class="contour-lines flex flex-col gap-5 bg-[#173f35] p-6 text-white sm:flex-row sm:items-end sm:justify-between md:p-8"
                >
                    <div>
                        <p
                            class="text-[10px] font-semibold tracking-[.2em] text-[#edbf79] uppercase"
                        >
                            Route reputation
                        </p>
                        <h2 class="font-serif-cn mt-2 text-2xl font-semibold">
                            真实参团者的线路评价
                        </h2>
                        <p class="mt-2 text-xs leading-6 text-white/55">
                            不同团期的真实反馈统一沉淀在本线路，便于后续参团人员比较参考。
                        </p>
                    </div>
                    <GroupReviewDialog
                        v-if="reviewableGroup"
                        :route-id="route.id"
                        :source-group-id="reviewableGroup.id"
                        :route-title="route.title"
                        :current-review="currentReview"
                    >
                        <button
                            type="button"
                            class="inline-flex h-11 shrink-0 items-center justify-center gap-2 rounded-full bg-[#e6b76f] px-6 text-sm font-semibold text-[#173e35]"
                        >
                            <Star class="size-4" />
                            {{
                                currentReview ? '修改我的评价' : '评价这条线路'
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
                                汇总 {{ reviewSummary?.count }} 位实际参团者
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
                            这条线路还没有评价
                        </p>
                        <p class="mt-1 text-xs text-[#8e8b82]">
                            完成过该线路团期的成员可以提交第一条真实反馈。
                        </p>
                    </div>
                </div>
            </section>

            <section
                v-if="recommendedGroups?.length"
                class="mt-6 rounded-[26px] border border-[#dfdcd0] bg-[#fffefa] p-5 md:p-8 dark:border-border dark:bg-card"
            >
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <p
                            class="text-[10px] font-semibold tracking-[.2em] text-[#b25c3d] uppercase"
                        >
                            Similar journeys
                        </p>
                        <h2
                            class="font-serif-cn mt-2 text-2xl font-semibold text-[#244a3d]"
                        >
                            推荐可报名的相似团队
                        </h2>
                        <p class="mt-2 text-xs text-[#8d8c83]">
                            优先推荐同线路团期，并结合目的地区域和行程天数匹配其他选择。
                        </p>
                    </div>
                    <Link
                        href="/groups"
                        class="text-xs font-semibold text-[#b45d3d]"
                        >查看全部组团</Link
                    >
                </div>
                <div class="mt-6 grid gap-4 md:grid-cols-2">
                    <Link
                        v-for="group in recommendedGroups"
                        :key="group.id"
                        :href="`/groups/${group.id}`"
                        class="group rounded-2xl border border-[#e2ded2] p-5 transition hover:-translate-y-0.5 hover:border-[#9eae9f] hover:shadow-[0_14px_35px_rgba(42,67,58,.08)]"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <span
                                    class="rounded-full bg-[#e7efe8] px-2.5 py-1 text-[10px] font-semibold text-[#386354]"
                                    >{{
                                        group.sameRoute
                                            ? '同线路团期'
                                            : '相似线路'
                                    }}</span
                                >
                                <h3
                                    class="font-serif-cn mt-3 text-lg font-semibold text-[#2b4e42] group-hover:text-[#b45d3d]"
                                >
                                    {{ group.title }}
                                </h3>
                            </div>
                            <ArrowRight class="size-4 text-[#9e9a90]" />
                        </div>
                        <p class="mt-2 text-xs text-[#88877f]">
                            {{ group.routeTitle }}
                        </p>
                        <div
                            class="mt-4 grid gap-2 rounded-xl bg-[#f4f2e9] p-3 text-[11px] text-[#666b64] sm:grid-cols-2"
                        >
                            <span class="inline-flex items-center gap-1.5"
                                ><MapPin class="size-3.5 text-[#b45d3d]" />{{
                                    group.location
                                }}</span
                            >
                            <span class="inline-flex items-center gap-1.5"
                                ><Clock3 class="size-3.5 text-[#b45d3d]" />{{
                                    group.date
                                }}</span
                            >
                        </div>
                        <div
                            class="mt-4 flex items-center justify-between text-[10px]"
                        >
                            <span class="text-[#737870]"
                                >{{ group.days }} 天 ·
                                {{ group.leader }}带队</span
                            >
                            <span class="font-semibold text-[#a65b40]">
                                剩余 {{ group.remaining }} 个名额
                            </span>
                        </div>
                        <div
                            class="mt-3 flex items-center gap-1.5 border-t border-[#ebe7dc] pt-3 text-[10px] text-[#777970]"
                        >
                            <Star
                                :class="[
                                    'size-3.5',
                                    group.routeRating != null &&
                                        'fill-[#d99a45] text-[#d99a45]',
                                ]"
                            />
                            <template v-if="group.routeRating != null">
                                线路口碑 {{ group.routeRating.toFixed(1) }} ·
                                {{ group.reviewCount }} 条评价
                            </template>
                            <template v-else>该线路暂无评价</template>
                        </div>
                    </Link>
                </div>
            </section>
        </div>
    </main>
</template>
