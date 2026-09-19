<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowLeft,
    BadgeCheck,
    CalendarDays,
    Check,
    Clock3,
    Hotel,
    MapPin,
    ShieldCheck,
    UserRoundCheck,
    UsersRound,
} from '@lucide/vue';
import { toast } from 'vue-sonner';
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
    status: 'pending' | 'approved' | 'rejected';
    message?: string;
    submittedAt: string;
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
    } | null;
    canManage?: boolean;
    isLeader?: boolean;
    applications?: ApplicationRecord[];
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
const progress = computed(() =>
    Math.min(100, (group.joined / group.capacity) * 100),
);

defineOptions({
    layout: {
        breadcrumbs: [
            { title: '组团广场', href: '/groups' },
            { title: '组团详情', href: '#' },
        ],
    },
});

function apply() {
    router.post(
        `/groups/${group.id}/applications`,
        { member_count: 1, family_members: [], message: '' },
        {
            preserveScroll: true,
            onSuccess: () => {
                applicationStatus.value = 'pending';
                toast.success('参团申请已提交', {
                    description: '团长将在 48 小时内完成审核。',
                });
            },
            onError: () => toast.error('提交失败，请检查报名状态'),
        },
    );
}

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
</script>

<template>
    <Head :title="group.title" />
    <main
        class="paper-grid min-h-full bg-[#f8f7f0] p-4 sm:p-6 lg:p-8 dark:bg-background"
    >
        <div class="mx-auto max-w-6xl">
            <Link
                href="/groups"
                class="inline-flex items-center gap-2 text-xs text-[#687169] hover:text-[#b25c3d]"
                ><ArrowLeft class="size-3.5" /> 返回组团广场</Link
            >
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
                        <span
                            :class="[
                                'rounded-full px-3 py-1 text-[10px] font-semibold',
                                group.status === '即将满员'
                                    ? 'bg-[#f6e5dd] text-[#ad5738]'
                                    : group.status === '已成团'
                                      ? 'bg-[#e7e9ef] text-[#586278]'
                                      : 'bg-[#e7efe8] text-[#386354]',
                            ]"
                            >{{ group.status }}</span
                        ><span
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
                    ><button
                        v-else-if="
                            group.status === '报名中' ||
                            group.status === '即将满员'
                        "
                        type="button"
                        class="mt-6 inline-flex h-12 items-center justify-center gap-2 rounded-full bg-[#c46140] px-6 text-sm font-semibold text-white transition hover:-translate-y-0.5"
                        @click="apply"
                    >
                        {{
                            applicationStatus === 'rejected'
                                ? '重新申请参加本团'
                                : '申请参加本团'
                        }}
                    </button>
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
                            团员申请审核
                        </h2>
                    </div>
                    <span class="text-xs text-[#8d8c83]"
                        >共 {{ applications?.length ?? 0 }} 条申请</span
                    >
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
                            </div>
                            <p class="mt-2 text-xs text-[#85847c]">
                                {{ application.memberCount }} 人 ·
                                {{ application.submittedAt }}
                                <template v-if="application.message">
                                    · {{ application.message }}
                                </template>
                            </p>
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
                                />提交后由团长审核确认
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
                            {{ group.deadline }}，团长将在提交后 48
                            小时内完成审核。
                        </p>
                    </div>
                </aside>
            </section>
        </div>
    </main>
</template>
