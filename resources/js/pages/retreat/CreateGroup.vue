<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowLeft,
    ArrowRight,
    BadgeCheck,
    CalendarDays,
    MapPin,
    UsersRound,
} from '@lucide/vue';
import { toast } from 'vue-sonner';
import RouteArtwork from '@/components/retreat/RouteArtwork.vue';
import { routeById  } from '@/data/retreat';
import type {RetreatRoute} from '@/data/retreat';

const props = defineProps<{ routeId: number; routeRecord?: RetreatRoute }>();
const route = props.routeRecord ?? routeById(props.routeId || 1);
const groupName = ref(
    `${route.location.split('·').pop()?.trim() ?? route.location}秋季疗休养团`,
);
const departure = ref('');
const returnDate = ref('');
const deadline = ref('');
const minPeople = ref(Number(route.people.match(/\d+/)?.[0] ?? 10));
const maxPeople = ref(Number(route.people.match(/\d+(?=\s*人)/)?.[0] ?? 20));

defineOptions({
    layout: {
        breadcrumbs: [
            { title: '线路库', href: '/routes' },
            { title: '创建组团', href: '#' },
        ],
    },
});

function publish() {
    if (
        !groupName.value ||
        !departure.value ||
        !returnDate.value ||
        !deadline.value
    ) {
        toast.error('请完善团期信息', {
            description: '组团名称、出行日期和报名截止时间不能为空。',
        });

        return;
    }

    router.post(
        '/groups',
        {
            retreat_route_id: route.id,
            title: groupName.value,
            departure_date: departure.value,
            return_date: returnDate.value,
            application_deadline: deadline.value,
            min_people: minPeople.value,
            max_people: maxPeople.value,
            meeting_info: '',
            notes: '',
        },
        {
            onError: () =>
                toast.error('发布失败', {
                    description: '请检查日期和人数是否符合线路审批范围。',
                }),
        },
    );
}
</script>

<template>
    <Head title="创建组团" />
    <main
        class="paper-grid min-h-full bg-[#f8f7f0] p-4 sm:p-6 lg:p-8 dark:bg-background"
    >
        <div class="mx-auto max-w-5xl">
            <Link
                :href="`/routes/${route.id}`"
                class="inline-flex items-center gap-2 text-xs text-[#687169] hover:text-[#b25c3d]"
                ><ArrowLeft class="size-3.5" /> 返回线路详情</Link
            >
            <div class="mt-5">
                <p
                    class="text-[10px] font-semibold tracking-[.2em] text-[#b25c3d] uppercase"
                >
                    Create a group
                </p>
                <h1
                    class="font-serif-cn mt-2 text-3xl font-semibold text-[#1d4237] md:text-4xl dark:text-foreground"
                >
                    为这条线路创建团期
                </h1>
                <p class="mt-3 text-sm text-[#7b7c74]">
                    线路已经完成审批，填写日期和人数后即可直接开放报名。
                </p>
            </div>
            <div class="mt-8 grid gap-6 lg:grid-cols-[320px_1fr]">
                <aside
                    class="overflow-hidden rounded-[24px] border border-[#dedbd0] bg-[#fffefa] lg:sticky lg:top-6 lg:self-start dark:border-border dark:bg-card"
                >
                    <RouteArtwork
                        :palette="route.palette"
                        :location="route.location"
                        :accent="route.accent"
                        :image="route.cover"
                        class="h-44"
                    />
                    <div class="p-5">
                        <p
                            class="font-serif-cn text-lg font-semibold text-[#284a40] dark:text-foreground"
                        >
                            {{ route.title }}
                        </p>
                        <p class="mt-2 text-xs leading-6 text-[#85857d]">
                            {{ route.summary }}
                        </p>
                        <div class="mt-4 flex gap-4 text-[10px] text-[#696e67]">
                            <span>{{ route.days }} 天</span
                            ><span>{{ route.people }}</span>
                        </div>
                        <div
                            class="mt-5 flex items-center gap-2 rounded-xl bg-[#e8f0ea] p-3 text-[10px] font-semibold text-[#396153]"
                        >
                            <BadgeCheck class="size-4" /> 已审批版本，可直接发布
                        </div>
                    </div>
                </aside>
                <section
                    class="rounded-[26px] border border-[#dedbd0] bg-[#fffefa] p-5 md:p-8 dark:border-border dark:bg-card"
                >
                    <h2
                        class="font-serif-cn text-xl font-semibold text-[#294b40]"
                    >
                        本次组团信息
                    </h2>
                    <div class="mt-6 space-y-5">
                        <label class="block"
                            ><span class="mb-2 block text-xs font-semibold"
                                >组团名称 *</span
                            ><input
                                v-model="groupName"
                                class="h-11 w-full rounded-xl border border-[#dad7ca] bg-[#faf9f3] px-4 text-sm outline-none focus:border-[#477467] dark:bg-background"
                        /></label>
                        <div class="grid gap-5 sm:grid-cols-2">
                            <label class="block"
                                ><span
                                    class="mb-2 flex items-center gap-2 text-xs font-semibold"
                                    ><CalendarDays
                                        class="size-3.5 text-[#b15d3d]"
                                    />
                                    出发日期 *</span
                                ><input
                                    v-model="departure"
                                    type="date"
                                    class="h-11 w-full rounded-xl border border-[#dad7ca] bg-[#faf9f3] px-4 text-sm dark:bg-background" /></label
                            ><label class="block"
                                ><span class="mb-2 block text-xs font-semibold"
                                    >返程日期 *</span
                                ><input
                                    v-model="returnDate"
                                    type="date"
                                    class="h-11 w-full rounded-xl border border-[#dad7ca] bg-[#faf9f3] px-4 text-sm dark:bg-background"
                            /></label>
                        </div>
                        <label class="block"
                            ><span class="mb-2 block text-xs font-semibold"
                                >报名截止日期 *</span
                            ><input
                                v-model="deadline"
                                type="date"
                                class="h-11 w-full rounded-xl border border-[#dad7ca] bg-[#faf9f3] px-4 text-sm dark:bg-background"
                        /></label>
                        <div>
                            <span
                                class="mb-2 flex items-center gap-2 text-xs font-semibold"
                                ><UsersRound class="size-3.5 text-[#b15d3d]" />
                                成团人数 *</span
                            >
                            <div
                                class="grid grid-cols-[1fr_auto_1fr] items-center gap-3"
                            >
                                <label
                                    ><span
                                        class="mb-1 block text-[10px] text-[#99978e]"
                                        >最低人数</span
                                    ><input
                                        v-model="minPeople"
                                        type="number"
                                        class="h-11 w-full rounded-xl border border-[#dad7ca] bg-[#faf9f3] px-4 text-sm dark:bg-background" /></label
                                ><span class="mt-5 text-[#aaa89e]">—</span
                                ><label
                                    ><span
                                        class="mb-1 block text-[10px] text-[#99978e]"
                                        >最高人数</span
                                    ><input
                                        v-model="maxPeople"
                                        type="number"
                                        class="h-11 w-full rounded-xl border border-[#dad7ca] bg-[#faf9f3] px-4 text-sm dark:bg-background"
                                /></label>
                            </div>
                            <p class="mt-2 text-[10px] text-[#99978e]">
                                人数需保持在已审批范围 {{ route.people }} 内。
                            </p>
                        </div>
                        <label class="block"
                            ><span
                                class="mb-2 flex items-center gap-2 text-xs font-semibold"
                                ><MapPin class="size-3.5 text-[#b15d3d]" />
                                集合信息</span
                            ><textarea
                                rows="3"
                                class="w-full resize-none rounded-xl border border-[#dad7ca] bg-[#faf9f3] p-4 text-sm dark:bg-background"
                                placeholder="可在成团后补充集合时间、地点与联系人"
                            /></label
                        ><label class="block"
                            ><span class="mb-2 block text-xs font-semibold"
                                >补充说明</span
                            ><textarea
                                rows="3"
                                class="w-full resize-none rounded-xl border border-[#dad7ca] bg-[#faf9f3] p-4 text-sm dark:bg-background"
                                placeholder="面向报名成员的其他说明"
                            />
                        </label>
                    </div>
                    <div
                        class="mt-7 flex justify-end gap-3 border-t border-[#e6e2d7] pt-5"
                    >
                        <button
                            type="button"
                            class="h-10 rounded-full border border-[#d4d0c4] px-5 text-xs font-semibold"
                            @click="toast.success('草稿已保存')"
                        >
                            保存草稿</button
                        ><button
                            type="button"
                            class="inline-flex h-10 items-center gap-2 rounded-full bg-[#c46140] px-6 text-xs font-semibold text-white"
                            @click="publish"
                        >
                            发布并开放报名 <ArrowRight class="size-3.5" />
                        </button>
                    </div>
                </section>
            </div>
        </div>
    </main>
</template>
