<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowLeft,
    ArrowRight,
    BadgeCheck,
    Clock3,
    FileText,
    Heart,
    Hotel,
    MapPin,
    ShieldCheck,
    UsersRound,
} from '@lucide/vue';
import RouteArtwork from '@/components/retreat/RouteArtwork.vue';
import {
    itineraryByRouteId,
    routeById
    
    
} from '@/data/retreat';
import type {ItineraryDay, RetreatRoute} from '@/data/retreat';

const props = defineProps<{
    routeId: number;
    routeRecord?: RetreatRoute;
    itineraryRecord?: ItineraryDay[];
}>();
const route = props.routeRecord ?? routeById(props.routeId);
const itinerary = props.itineraryRecord ?? itineraryByRouteId(props.routeId);

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
                                校工会终审通过
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
        </div>
    </main>
</template>
