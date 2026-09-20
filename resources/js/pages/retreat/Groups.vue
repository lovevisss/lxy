<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import {
    CalendarDays,
    Check,
    Clock3,
    Download,
    MapPin,
    Plus,
    Search,
    SlidersHorizontal,
    Star,
    UserRoundCheck,
    UsersRound,
} from '@lucide/vue';
import PageIntro from '@/components/retreat/PageIntro.vue';
import GroupApplicationDialog from '@/components/retreat/GroupApplicationDialog.vue';
import { retreatGroups } from '@/data/retreat';
import type { RetreatGroup } from '@/data/retreat';

const props = defineProps<{ groupRecords?: RetreatGroup[] }>();

defineOptions({
    layout: { breadcrumbs: [{ title: '组团广场', href: '/groups' }] },
});

const active = ref('全部组团');
const joined = ref(new Set<number>());
const search = ref('');
const region = ref('全部目的地');
const duration = ref('全部天数');
const startFrom = ref('');
const startTo = ref('');
const tabs = ['全部组团', '开放报名', '即将出发', '我的组团'];
const allGroups = computed(() =>
    props.groupRecords !== undefined ? props.groupRecords : retreatGroups,
);
const regions = computed(() => [
    '全部目的地',
    ...new Set(allGroups.value.map((group) => group.region).filter(Boolean)),
]);
const durations = computed(() => [
    '全部天数',
    ...new Set(
        allGroups.value
            .map((group) => group.days)
            .filter((days): days is number => Boolean(days))
            .sort((a, b) => a - b)
            .map((days) => `${days}天`),
    ),
]);
const visibleGroups = computed(() => {
    let groups: RetreatGroup[];

    if (active.value === '即将出发') {
        groups = allGroups.value.filter((group) => group.status === '已成团');
    } else if (active.value === '我的组团') {
        groups = allGroups.value.filter(
            (group) => group.isLeader || group.membershipStatus,
        );
    } else if (active.value === '开放报名') {
        groups = allGroups.value.filter(
            (group) => group.status === '报名中' || group.status === '即将满员',
        );
    } else {
        groups = allGroups.value;
    }

    const keyword = search.value.trim().toLowerCase();

    return groups.filter((group) => {
        const matchesSearch =
            !keyword ||
            [group.title, group.route, group.leader, group.location]
                .filter(Boolean)
                .some((value) => value?.toLowerCase().includes(keyword));
        const matchesRegion =
            region.value === '全部目的地' || group.region === region.value;
        const matchesDuration =
            duration.value === '全部天数' ||
            `${group.days}天` === duration.value;
        const matchesFrom =
            !startFrom.value ||
            Boolean(
                group.departureDate && group.departureDate >= startFrom.value,
            );
        const matchesTo =
            !startTo.value ||
            Boolean(
                group.departureDate && group.departureDate <= startTo.value,
            );

        return (
            matchesSearch &&
            matchesRegion &&
            matchesDuration &&
            matchesFrom &&
            matchesTo
        );
    });
});

function markApplied(groupId: number) {
    joined.value = new Set([...joined.value, groupId]);
}

function resetFilters() {
    search.value = '';
    region.value = '全部目的地';
    duration.value = '全部天数';
    startFrom.value = '';
    startTo.value = '';
}
</script>

<template>
    <Head title="组团广场" />
    <main
        class="paper-grid min-h-full bg-[#f8f7f0] p-4 sm:p-6 lg:p-8 dark:bg-background"
    >
        <div class="mx-auto max-w-[1480px]">
            <PageIntro
                eyebrow="Travel together"
                title="与熟悉的人，一起出发"
                description="查看正在招募的疗休养团队。提交申请后由团长确认，你也可以从线路库选择一条线路发起自己的团队。"
            >
                <Link
                    href="/routes"
                    class="inline-flex h-11 items-center gap-2 rounded-full bg-[#1b463b] px-5 text-sm font-semibold text-white transition hover:-translate-y-0.5"
                    ><Plus class="size-4" /> 发起组团</Link
                >
            </PageIntro>

            <div
                class="mt-8 flex flex-col gap-4 border-b border-[#dbd9ce] pb-4 md:flex-row md:items-center md:justify-between"
            >
                <div
                    class="flex gap-1 rounded-full bg-[#ece9df] p-1 dark:bg-muted"
                >
                    <button
                        v-for="tab in tabs"
                        :key="tab"
                        type="button"
                        :class="[
                            'rounded-full px-4 py-2 text-xs transition',
                            active === tab
                                ? 'bg-[#fffefa] font-semibold text-[#254c40] shadow-sm dark:bg-card'
                                : 'text-[#797970]',
                        ]"
                        @click="active = tab"
                    >
                        {{ tab }}
                    </button>
                </div>
                <label
                    class="flex h-10 w-full items-center gap-2 rounded-full border border-[#dddacf] bg-[#fffefa]/80 px-4 text-xs text-[#8a887e] md:w-64 dark:border-border dark:bg-card"
                    ><Search class="size-3.5 shrink-0" /><input
                        v-model="search"
                        class="min-w-0 flex-1 bg-transparent outline-none"
                        placeholder="搜索组团名称、线路或团长"
                /></label>
            </div>

            <div
                class="mt-4 grid gap-3 rounded-2xl border border-[#dfdcd0] bg-[#fffefa]/75 p-4 md:grid-cols-[1fr_1fr_1.25fr_auto] md:items-end dark:border-border dark:bg-card"
            >
                <label class="block">
                    <span
                        class="mb-2 flex items-center gap-1.5 text-[10px] font-semibold text-[#73766f]"
                        ><SlidersHorizontal class="size-3" />目的地区域</span
                    >
                    <select
                        v-model="region"
                        class="h-10 w-full rounded-xl border border-[#d9d5c9] bg-[#faf9f3] px-3 text-xs outline-none focus:border-[#55786d] dark:bg-background"
                    >
                        <option
                            v-for="item in regions"
                            :key="item"
                            :value="item"
                        >
                            {{ item }}
                        </option>
                    </select>
                </label>
                <label class="block">
                    <span
                        class="mb-2 block text-[10px] font-semibold text-[#73766f]"
                        >行程天数</span
                    >
                    <select
                        v-model="duration"
                        class="h-10 w-full rounded-xl border border-[#d9d5c9] bg-[#faf9f3] px-3 text-xs outline-none focus:border-[#55786d] dark:bg-background"
                    >
                        <option
                            v-for="item in durations"
                            :key="item"
                            :value="item"
                        >
                            {{ item }}
                        </option>
                    </select>
                </label>
                <div>
                    <span
                        class="mb-2 block text-[10px] font-semibold text-[#73766f]"
                        >出发时段</span
                    >
                    <div
                        class="grid grid-cols-[1fr_auto_1fr] items-center gap-2"
                    >
                        <input
                            v-model="startFrom"
                            type="date"
                            class="h-10 min-w-0 rounded-xl border border-[#d9d5c9] bg-[#faf9f3] px-3 text-xs outline-none focus:border-[#55786d] dark:bg-background"
                        />
                        <span class="text-[#aaa79d]">—</span>
                        <input
                            v-model="startTo"
                            type="date"
                            class="h-10 min-w-0 rounded-xl border border-[#d9d5c9] bg-[#faf9f3] px-3 text-xs outline-none focus:border-[#55786d] dark:bg-background"
                        />
                    </div>
                </div>
                <button
                    type="button"
                    class="h-10 rounded-full border border-[#d4d0c4] px-4 text-xs font-semibold text-[#687169]"
                    @click="resetFilters"
                >
                    重置筛选
                </button>
            </div>

            <section class="mt-6 grid gap-5 lg:grid-cols-2">
                <article
                    v-for="(group, index) in visibleGroups"
                    :key="group.id"
                    class="enter-up overflow-hidden rounded-[24px] border border-[#dfded3] bg-[#fffefa] p-5 md:p-6 dark:border-border dark:bg-card"
                    :style="{ animationDelay: `${80 + index * 55}ms` }"
                >
                    <div class="flex items-start gap-4">
                        <div
                            :class="[
                                'contour-lines hidden size-24 shrink-0 rounded-2xl bg-gradient-to-br sm:block',
                                group.palette,
                            ]"
                        >
                            <div
                                class="grid size-full place-items-center text-white/90"
                            >
                                <MapPin class="size-6" />
                            </div>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <span
                                        :class="[
                                            'rounded-full px-2.5 py-1 text-[10px] font-semibold',
                                            group.status === '即将满员'
                                                ? 'bg-[#f6e5dd] text-[#ad5738]'
                                                : group.status === '未成团' ||
                                                    group.status === '已取消'
                                                  ? 'bg-[#f2e8e4] text-[#94533f]'
                                                  : 'bg-[#e7efe8] text-[#386354]',
                                        ]"
                                        >{{ group.status }}</span
                                    >
                                    <span
                                        class="ml-1.5 rounded-full bg-[#f2eee4] px-2.5 py-1 text-[10px] font-semibold text-[#775c48]"
                                    >
                                        {{
                                            group.approvalMode === 'automatic'
                                                ? '自动参团'
                                                : '团长审核'
                                        }}
                                    </span>
                                    <Link :href="`/groups/${group.id}`">
                                        <h2
                                            class="font-serif-cn mt-3 text-xl font-semibold text-[#24473c] transition hover:text-[#b45d3d] dark:text-foreground"
                                        >
                                            {{ group.title }}
                                        </h2>
                                    </Link>
                                </div>
                                <span
                                    class="shrink-0 text-[10px] text-[#a15a3e]"
                                    >{{ group.deadline }}</span
                                >
                            </div>
                            <p class="mt-2 truncate text-xs text-[#88877f]">
                                {{ group.route }}
                            </p>
                        </div>
                    </div>
                    <div
                        class="mt-5 grid gap-3 rounded-2xl bg-[#f4f2e9] p-4 text-xs text-[#646860] sm:grid-cols-2 dark:bg-muted"
                    >
                        <div class="flex items-center gap-2">
                            <CalendarDays class="size-4 text-[#b45e3e]" />{{
                                group.date
                            }}
                        </div>
                        <div class="flex items-center gap-2">
                            <UserRoundCheck class="size-4 text-[#b45e3e]" />{{
                                group.leader
                            }}
                            · {{ group.department }}
                        </div>
                    </div>
                    <div class="mt-5">
                        <div class="flex items-center justify-between text-xs">
                            <span
                                class="inline-flex items-center gap-1.5 text-[#60645e]"
                                ><UsersRound class="size-3.5" /> 已确认
                                {{ group.joined }} 人</span
                            ><span class="text-[#99978d]"
                                >最低 {{ group.min }} · 上限
                                {{ group.capacity }}</span
                            >
                        </div>
                        <div
                            class="mt-2.5 h-2 overflow-hidden rounded-full bg-[#ebe8dd] dark:bg-background"
                        >
                            <div
                                class="h-full rounded-full bg-gradient-to-r from-[#2d6757] to-[#d6a961]"
                                :style="{
                                    width: `${Math.min(100, (group.joined / group.capacity) * 100)}%`,
                                }"
                            />
                        </div>
                    </div>
                    <div
                        class="mt-5 flex flex-wrap items-center justify-between gap-3"
                    >
                        <Link
                            :href="`/groups/${group.id}`"
                            class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-[#45685c] hover:text-[#b45d3d]"
                            ><Clock3 class="size-3.5" /> 查看完整日程</Link
                        ><a
                            v-if="group.attachmentUrl"
                            :href="group.attachmentUrl"
                            class="mr-auto inline-flex items-center gap-1.5 text-[11px] font-semibold text-[#a75b40]"
                            ><Download class="size-3.5" /> 活动 PDF</a
                        ><Link
                            v-if="group.canReview && !group.reviewed"
                            :href="`/groups/${group.id}`"
                            class="inline-flex h-9 items-center gap-2 rounded-full bg-[#c56340] px-4 text-xs font-semibold text-white"
                        >
                            <Star class="size-3.5" /> 待评价</Link
                        ><span
                            v-else-if="group.reviewed"
                            class="inline-flex h-9 items-center gap-2 rounded-full bg-[#f2eadb] px-4 text-xs font-semibold text-[#8a6634]"
                        >
                            <Star class="size-3.5 fill-current" /> 已评价</span
                        ><Link
                            v-else-if="
                                group.status === '已成团' &&
                                group.finalConfirmationStatus === 'pending'
                            "
                            :href="`/groups/${group.id}`"
                            class="inline-flex h-9 items-center gap-2 rounded-full bg-[#fff0d5] px-4 text-xs font-semibold text-[#94632f]"
                        >
                            <Clock3 class="size-3.5" /> 待最终确认</Link
                        ><button
                            v-else-if="
                                group.finalConfirmationStatus === 'confirmed'
                            "
                            type="button"
                            disabled
                            class="inline-flex h-9 items-center gap-2 rounded-full bg-[#e6eee8] px-4 text-xs font-semibold text-[#396052]"
                        >
                            <Check class="size-3.5" /> 最终确认完成</button
                        ><button
                            v-else-if="
                                group.finalConfirmationStatus === 'declined'
                            "
                            type="button"
                            disabled
                            class="inline-flex h-9 items-center gap-2 rounded-full bg-[#f2e8e4] px-4 text-xs font-semibold text-[#94533f]"
                        >
                            无法参加</button
                        ><button
                            v-else-if="group.isLeader"
                            type="button"
                            disabled
                            class="inline-flex h-9 items-center gap-2 rounded-full bg-[#dfeae4] px-4 text-xs font-semibold text-[#2f5b4c]"
                        >
                            <Check class="size-3.5" /> 团长 · 已加入</button
                        ><button
                            v-else-if="group.membershipStatus === 'approved'"
                            type="button"
                            disabled
                            class="inline-flex h-9 items-center gap-2 rounded-full bg-[#e6eee8] px-4 text-xs font-semibold text-[#396052]"
                        >
                            <Check class="size-3.5" /> 已确认参团</button
                        ><button
                            v-else-if="
                                group.membershipStatus === 'pending' ||
                                joined.has(group.id)
                            "
                            type="button"
                            disabled
                            class="inline-flex h-9 items-center gap-2 rounded-full bg-[#f2eadb] px-4 text-xs font-semibold text-[#8a6634]"
                        >
                            <Clock3 class="size-3.5" /> 等待团长审核</button
                        ><GroupApplicationDialog
                            v-else-if="
                                (group.status === '报名中' ||
                                    group.status === '即将满员') &&
                                group.joined < group.capacity
                            "
                            :group-id="group.id"
                            :group-title="group.title"
                            :max-participants="group.capacity - group.joined"
                            :approval-mode="group.approvalMode"
                            @submitted="markApplied(group.id)"
                        >
                            <button
                                type="button"
                                class="h-9 rounded-full bg-[#c56340] px-5 text-xs font-semibold text-white transition hover:-translate-y-0.5 hover:bg-[#b45637]"
                            >
                                {{
                                    group.membershipStatus === 'rejected'
                                        ? '重新申请'
                                        : '申请参团'
                                }}
                            </button>
                        </GroupApplicationDialog>
                        <span
                            v-else
                            class="rounded-full bg-[#eceee9] px-4 py-2 text-[11px] text-[#777b73]"
                            >{{ group.status }}</span
                        >
                    </div>
                </article>
                <div
                    v-if="!visibleGroups.length"
                    class="grid min-h-64 place-items-center rounded-[24px] border border-dashed border-[#d8d3c6] bg-[#fffefa]/60 text-center lg:col-span-2"
                >
                    <div>
                        <Search class="mx-auto size-7 text-[#789589]" />
                        <p class="font-serif-cn mt-3 text-lg text-[#315348]">
                            没有符合条件的组团
                        </p>
                        <button
                            type="button"
                            class="mt-2 text-xs font-semibold text-[#b45d3d]"
                            @click="resetFilters"
                        >
                            清空筛选条件
                        </button>
                    </div>
                </div>
            </section>
        </div>
    </main>
</template>
