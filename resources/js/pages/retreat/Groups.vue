<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    CalendarDays,
    Check,
    Clock3,
    MapPin,
    Plus,
    Search,
    UserRoundCheck,
    UsersRound,
} from '@lucide/vue';
import { toast } from 'vue-sonner';
import PageIntro from '@/components/retreat/PageIntro.vue';
import { retreatGroups } from '@/data/retreat';
import type { RetreatGroup } from '@/data/retreat';

const props = defineProps<{ groupRecords?: RetreatGroup[] }>();

defineOptions({
    layout: { breadcrumbs: [{ title: '组团广场', href: '/groups' }] },
});

const active = ref('开放报名');
const joined = ref(new Set<number>());
const tabs = ['开放报名', '即将出发', '我的组团'];
const allGroups = computed(() =>
    props.groupRecords !== undefined ? props.groupRecords : retreatGroups,
);
const visibleGroups = computed(() => {
    if (active.value === '即将出发') {
        return allGroups.value.filter((group) => group.status === '已成团');
    }

    if (active.value === '我的组团') {
        return allGroups.value.filter(
            (group) => group.isLeader || group.membershipStatus,
        );
    }

    return allGroups.value.filter((group) => group.status !== '已成团');
});

function apply(groupId: number, title: string) {
    router.post(
        `/groups/${groupId}/applications`,
        { member_count: 1, family_members: [], message: '' },
        {
            preserveScroll: true,
            onSuccess: () => {
                joined.value = new Set([...joined.value, groupId]);
                toast.success('参团申请已提交', {
                    description: `“${title}”已进入团长审核。`,
                });
            },
            onError: () => toast.error('提交失败，请检查报名状态'),
        },
    );
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
                    ><Search class="size-3.5" />搜索组团名称或团长</label
                >
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
                                                : 'bg-[#e7efe8] text-[#386354]',
                                        ]"
                                        >{{ group.status }}</span
                                    >
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
                        ><button
                            v-if="group.isLeader"
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
                        ><button
                            v-else-if="
                                group.status === '报名中' ||
                                group.status === '即将满员'
                            "
                            type="button"
                            class="h-9 rounded-full bg-[#c56340] px-5 text-xs font-semibold text-white transition hover:-translate-y-0.5 hover:bg-[#b45637]"
                            @click="apply(group.id, group.title)"
                        >
                            {{
                                group.membershipStatus === 'rejected'
                                    ? '重新申请'
                                    : '申请参团'
                            }}
                        </button>
                        <span
                            v-else
                            class="rounded-full bg-[#eceee9] px-4 py-2 text-[11px] text-[#777b73]"
                            >{{ group.status }}</span
                        >
                    </div>
                </article>
            </section>
        </div>
    </main>
</template>
