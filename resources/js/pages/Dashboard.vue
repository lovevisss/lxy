<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    ArrowRight,
    BadgeCheck,
    BellRing,
    CalendarDays,
    ChevronRight,
    FilePenLine,
    MapPinned,
    Sparkles,
    UsersRound,
} from '@lucide/vue';
import { computed } from 'vue';
import RouteArtwork from '@/components/retreat/RouteArtwork.vue';
import type { RetreatGroup, RetreatRoute } from '@/data/retreat';

type TodoRecord = {
    id: string;
    title: string;
    description: string;
    href: string;
    kind: 'approval' | 'application';
};

const props = defineProps<{
    dashboard: {
        approvedRouteCount: number;
        openGroupCount: number;
        todoCount: number;
        featuredRoute: RetreatRoute | null;
        nextGroup: RetreatGroup | null;
        todos: TodoRecord[];
    };
}>();

const page = usePage();

defineOptions({
    layout: {
        breadcrumbs: [{ title: '工作台', href: '/dashboard' }],
    },
});

const featured = computed(() => props.dashboard.featuredRoute);
const activeGroup = computed(() => props.dashboard.nextGroup);
const userName = computed(() => page.props.auth.user.name);
const canApprove = computed(
    () =>
        page.props.auth.user.can_route_approve ||
        page.props.auth.user.can_group_approve,
);
const currentDate = new Intl.DateTimeFormat('zh-CN', {
    month: 'long',
    day: 'numeric',
    weekday: 'long',
}).format(new Date());

const metrics = computed(() => [
    {
        label: '开放线路',
        value: String(props.dashboard.approvedRouteCount).padStart(2, '0'),
        note: '审批通过可组团',
        icon: MapPinned,
        tone: 'bg-[#e4eee7] text-[#245547]',
    },
    {
        label: '报名中的团',
        value: String(props.dashboard.openGroupCount).padStart(2, '0'),
        note: '当前开放报名',
        icon: UsersRound,
        tone: 'bg-[#f1e9d7] text-[#8a6331]',
    },
    {
        label: '我的待办',
        value: String(props.dashboard.todoCount).padStart(2, '0'),
        note: '审批与报名审核',
        icon: BellRing,
        tone: 'bg-[#f5e4dc] text-[#a25036]',
    },
]);
</script>

<template>
    <Head title="疗休养工作台" />

    <main
        class="paper-grid min-h-full bg-[#f8f7f0] p-4 sm:p-6 lg:p-8 dark:bg-background"
    >
        <div class="mx-auto max-w-[1480px] space-y-6">
            <section class="enter-up grid gap-5 xl:grid-cols-[1.45fr_.75fr]">
                <div
                    class="contour-lines relative min-h-[315px] overflow-hidden rounded-[28px] bg-[#173e35] p-6 text-white shadow-[0_24px_70px_rgba(26,62,53,.16)] md:p-9"
                >
                    <div class="relative z-10 max-w-xl">
                        <div
                            class="mb-7 inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/8 px-3 py-1.5 text-[11px] text-white/75 backdrop-blur-sm"
                        >
                            <Sparkles class="size-3.5 text-[#efc27f]" />
                            秋季疗休养计划正在进行
                        </div>
                        <p class="text-xs tracking-[0.2em] text-[#e9bd7c]">
                            {{ currentDate }}
                        </p>
                        <h1
                            class="font-serif-cn mt-3 text-4xl leading-[1.2] font-semibold tracking-tight md:text-5xl"
                        >
                            {{ userName }}，您好<br />去山水间，松一松弦
                        </h1>
                        <p
                            class="mt-5 max-w-lg text-sm leading-7 text-white/58"
                        >
                            当前共有 {{ dashboard.openGroupCount }}
                            个团开放报名。你也可以带上一份心仪的行程，从一条新线路开始组团。
                        </p>
                        <div class="mt-7 flex flex-wrap gap-3">
                            <Link
                                href="/groups"
                                class="inline-flex h-10 items-center gap-2 rounded-full bg-[#e6b76f] px-5 text-sm font-semibold text-[#173e35] transition hover:-translate-y-0.5 hover:bg-[#efc582]"
                            >
                                浏览开放组团 <ArrowRight class="size-4" />
                            </Link>
                            <Link
                                href="/routes/create"
                                class="inline-flex h-10 items-center gap-2 rounded-full border border-white/20 bg-white/8 px-5 text-sm text-white transition hover:bg-white/14"
                            >
                                <FilePenLine class="size-4" /> 发起线路
                            </Link>
                        </div>
                    </div>
                    <div
                        class="absolute right-[-6%] bottom-[-20%] h-[92%] w-[48%] opacity-80"
                    >
                        <svg
                            viewBox="0 0 500 380"
                            class="size-full"
                            aria-hidden="true"
                        >
                            <circle
                                cx="357"
                                cy="82"
                                r="34"
                                fill="#cf6e47"
                                opacity=".9"
                            />
                            <path
                                d="M30 339 188 134l74 100 56-73 152 178Z"
                                fill="#2d5a4e"
                            />
                            <path
                                d="m102 340 126-147 46 61 45-58 123 144Z"
                                fill="#477467"
                                opacity=".95"
                            />
                            <path
                                d="m198 340 94-102 31 41 40-39 92 100Z"
                                fill="#789588"
                                opacity=".65"
                            />
                            <path
                                d="M96 341c96-57 238-64 374-5"
                                fill="none"
                                stroke="#f4d39b"
                                stroke-width="3"
                                opacity=".32"
                            />
                        </svg>
                    </div>
                </div>

                <div
                    v-if="activeGroup"
                    class="enter-up rounded-[28px] border border-[#dfded3] bg-[#fffefa]/90 p-6 shadow-[0_18px_60px_rgba(52,60,48,.07)] dark:border-border dark:bg-card"
                    style="animation-delay: 80ms"
                >
                    <div class="flex items-start justify-between">
                        <div>
                            <p
                                class="text-[10px] font-semibold tracking-[0.18em] text-[#a25c40] uppercase"
                            >
                                My journey
                            </p>
                            <h2
                                class="font-serif-cn mt-2 text-2xl font-semibold text-[#193d34] dark:text-foreground"
                            >
                                下一段行程
                            </h2>
                        </div>
                        <span
                            class="rounded-full bg-[#e7f0e9] px-3 py-1 text-[10px] font-semibold text-[#356554]"
                            >{{ activeGroup.status }}</span
                        >
                    </div>
                    <div class="my-6 h-px bg-[#e8e5da] dark:bg-border" />
                    <p class="text-xs text-[#8c8b80]">
                        {{ activeGroup.route }}
                    </p>
                    <p
                        class="font-serif-cn mt-2 text-xl font-semibold text-[#2a463d] dark:text-foreground"
                    >
                        {{ activeGroup.title }}
                    </p>
                    <div
                        class="mt-6 space-y-4 text-sm text-[#595e57] dark:text-muted-foreground"
                    >
                        <div class="flex items-center gap-3">
                            <CalendarDays
                                class="size-4 text-[#b25e3e]"
                            /><span>{{ activeGroup.date }}</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <UsersRound class="size-4 text-[#b25e3e]" /><span
                                >{{ activeGroup.leader }}老师带队 · 已有
                                {{ activeGroup.joined }} 人</span
                            >
                        </div>
                    </div>
                    <div
                        class="mt-6 rounded-2xl bg-[#f2f0e7] p-4 dark:bg-muted"
                    >
                        <div class="flex items-center justify-between text-xs">
                            <span>出发准备</span
                            ><span class="font-semibold text-[#2d5e50]"
                                >2 / 4</span
                            >
                        </div>
                        <div
                            class="mt-3 h-1.5 overflow-hidden rounded-full bg-white dark:bg-background"
                        >
                            <div
                                class="h-full w-1/2 rounded-full bg-[#c76a47]"
                            />
                        </div>
                        <p class="mt-3 text-[11px] text-[#89877d]">
                            请在 10 月 20 日前确认随行家属信息
                        </p>
                    </div>
                </div>
                <div
                    v-else
                    class="enter-up grid min-h-[315px] place-items-center rounded-[28px] border border-[#dfded3] bg-[#fffefa]/90 p-8 text-center dark:border-border dark:bg-card"
                    style="animation-delay: 80ms"
                >
                    <div>
                        <CalendarDays class="mx-auto size-8 text-[#709285]" />
                        <h2
                            class="font-serif-cn mt-4 text-2xl font-semibold text-[#193d34]"
                        >
                            暂无已确认行程
                        </h2>
                        <p class="mt-2 text-xs text-[#8c8b80]">
                            从开放组团中提交报名，审核通过后将在这里显示。
                        </p>
                        <Link
                            href="/groups"
                            class="mt-5 inline-flex h-9 items-center rounded-full bg-[#1d4b3e] px-5 text-xs font-semibold text-white"
                            >浏览组团</Link
                        >
                    </div>
                </div>
            </section>

            <section class="grid gap-4 md:grid-cols-3">
                <article
                    v-for="(metric, index) in metrics"
                    :key="metric.label"
                    class="enter-up flex items-center gap-4 rounded-2xl border border-[#e1dfd5] bg-[#fffefa]/85 p-5 dark:border-border dark:bg-card"
                    :style="{ animationDelay: `${120 + index * 55}ms` }"
                >
                    <div
                        :class="[
                            'grid size-12 shrink-0 place-items-center rounded-2xl',
                            metric.tone,
                        ]"
                    >
                        <component :is="metric.icon" class="size-5" />
                    </div>
                    <div>
                        <p class="text-xs text-[#838278]">{{ metric.label }}</p>
                        <div class="mt-1 flex items-end gap-2">
                            <span
                                class="font-serif-cn text-2xl font-semibold text-[#233f36] dark:text-foreground"
                                >{{ metric.value }}</span
                            ><span class="pb-0.5 text-[10px] text-[#aaa89e]">{{
                                metric.note
                            }}</span>
                        </div>
                    </div>
                </article>
            </section>

            <section class="grid gap-6 xl:grid-cols-[1.2fr_.8fr]">
                <div
                    class="rounded-[26px] border border-[#dfded3] bg-[#fffefa]/90 p-5 md:p-6 dark:border-border dark:bg-card"
                >
                    <div class="mb-5 flex items-end justify-between">
                        <div>
                            <p
                                class="text-[10px] font-semibold tracking-[0.18em] text-[#a25c40] uppercase"
                            >
                                Featured route
                            </p>
                            <h2
                                class="font-serif-cn mt-1 text-2xl font-semibold text-[#1f4036] dark:text-foreground"
                            >
                                本周推荐线路
                            </h2>
                        </div>
                        <Link
                            href="/routes"
                            class="inline-flex items-center gap-1 text-xs font-medium text-[#41695c] hover:text-[#b55e3d]"
                            >查看全部 <ChevronRight class="size-3.5"
                        /></Link>
                    </div>
                    <div
                        v-if="featured"
                        class="grid overflow-hidden rounded-2xl border border-[#e5e1d5] md:grid-cols-[.82fr_1.18fr] dark:border-border"
                    >
                        <RouteArtwork
                            :palette="featured.palette"
                            :location="featured.location"
                            :accent="featured.accent"
                            :image="featured.cover"
                            class="min-h-52"
                        />
                        <div class="p-6">
                            <div class="flex flex-wrap gap-2">
                                <span
                                    v-for="tag in featured.tags"
                                    :key="tag"
                                    class="rounded-full bg-[#f1eee3] px-2.5 py-1 text-[10px] text-[#696b63] dark:bg-muted"
                                    >{{ tag }}</span
                                >
                            </div>
                            <h3
                                class="font-serif-cn mt-4 text-2xl font-semibold text-[#25473d] dark:text-foreground"
                            >
                                {{ featured.title }}
                            </h3>
                            <p class="mt-3 text-xs leading-6 text-[#797a72]">
                                {{ featured.summary }}
                            </p>
                            <div
                                class="mt-5 flex items-center gap-5 text-xs text-[#5d625c]"
                            >
                                <span>{{ featured.days }} 天行程</span
                                ><span>{{ featured.people }}</span
                                ><span>审批版本 V2</span>
                            </div>
                            <Link
                                :href="`/routes/${featured.id}`"
                                class="mt-6 inline-flex items-center gap-2 text-sm font-semibold text-[#b45e3e]"
                                >查看日程并发起组团 <ArrowRight class="size-4"
                            /></Link>
                        </div>
                    </div>
                    <div
                        v-else
                        class="grid min-h-56 place-items-center rounded-2xl border border-dashed border-[#d8d4c8] text-center"
                    >
                        <div>
                            <MapPinned class="mx-auto size-7 text-[#7c998e]" />
                            <p class="mt-3 text-sm text-[#5f6b65]">
                                暂无审批通过的线路
                            </p>
                            <Link
                                href="/routes/create"
                                class="mt-3 inline-flex text-xs font-semibold text-[#b45e3e]"
                                >发起第一条线路</Link
                            >
                        </div>
                    </div>
                </div>

                <div
                    class="rounded-[26px] border border-[#dfded3] bg-[#fffefa]/90 p-5 md:p-6 dark:border-border dark:bg-card"
                >
                    <div class="flex items-end justify-between">
                        <div>
                            <p
                                class="text-[10px] font-semibold tracking-[0.18em] text-[#a25c40] uppercase"
                            >
                                To do
                            </p>
                            <h2
                                class="font-serif-cn mt-1 text-2xl font-semibold text-[#1f4036] dark:text-foreground"
                            >
                                待处理事项
                            </h2>
                        </div>
                        <Link
                            :href="canApprove ? '/approvals' : '/groups'"
                            class="text-xs text-[#41695c]"
                            >{{
                                canApprove ? '进入审批中心' : '查看我的组团'
                            }}</Link
                        >
                    </div>
                    <div class="mt-5 space-y-2">
                        <Link
                            v-for="(todo, index) in dashboard.todos"
                            :key="todo.id"
                            :href="todo.href"
                            :class="[
                                'group flex items-center gap-4 rounded-2xl border border-transparent p-4 transition hover:border-[#d9d6ca] hover:bg-[#f7f5ed] dark:hover:bg-muted',
                                index === 0 ? 'bg-[#f3f1e8] dark:bg-muted' : '',
                            ]"
                        >
                            <div
                                :class="[
                                    'grid size-10 place-items-center rounded-xl',
                                    todo.kind === 'approval'
                                        ? 'bg-[#e6eee7] text-[#376253]'
                                        : 'bg-[#f3e5dd] text-[#b45e3e]',
                                ]"
                            >
                                <BadgeCheck
                                    v-if="todo.kind === 'approval'"
                                    class="size-4"
                                />
                                <UsersRound v-else class="size-4" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium">
                                    {{ todo.title }}
                                </p>
                                <p class="mt-1 text-[10px] text-[#96948a]">
                                    {{ todo.description }}
                                </p>
                            </div>
                            <ChevronRight
                                class="size-4 text-[#aaa89d] transition group-hover:translate-x-0.5"
                            />
                        </Link>
                        <div
                            v-if="!dashboard.todos.length"
                            class="grid min-h-44 place-items-center rounded-2xl bg-[#f3f1e8] text-center dark:bg-muted"
                        >
                            <div>
                                <BadgeCheck
                                    class="mx-auto size-7 text-[#719184]"
                                />
                                <p class="mt-3 text-sm text-[#4f655d]">
                                    当前没有待处理事项
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>
</template>
