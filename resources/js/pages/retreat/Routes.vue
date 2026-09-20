<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    CircleDollarSign,
    FileSpreadsheet,
    Clock3,
    Heart,
    Plus,
    Search,
    SlidersHorizontal,
    Star,
    UsersRound,
} from '@lucide/vue';
import PageIntro from '@/components/retreat/PageIntro.vue';
import RouteArtwork from '@/components/retreat/RouteArtwork.vue';
import { retreatRoutes } from '@/data/retreat';
import type { RetreatRoute } from '@/data/retreat';

const props = defineProps<{
    routeRecords?: RetreatRoute[];
    canImport?: boolean;
}>();

defineOptions({
    layout: { breadcrumbs: [{ title: '线路库', href: '/routes' }] },
});

const search = ref('');
const region = ref('全部');
const allRoutes = computed(() =>
    props.routeRecords !== undefined ? props.routeRecords : retreatRoutes,
);
const favorites = ref(
    new Set(
        retreatRoutes
            .filter((route) => route.favorite)
            .map((route) => route.id),
    ),
);
const regions = ['全部', '华东', '西南', '华南', '东北'];

const filteredRoutes = computed(() =>
    allRoutes.value.filter((route) => {
        const matchRegion =
            region.value === '全部' || route.region === region.value;
        const keyword = search.value.trim().toLowerCase();
        const matchSearch =
            !keyword ||
            `${route.title}${route.location}${route.summary}${route.tags.join('')}`
                .toLowerCase()
                .includes(keyword);

        return matchRegion && matchSearch;
    }),
);

function toggleFavorite(id: number) {
    const next = new Set(favorites.value);

    if (next.has(id)) {
        next.delete(id);
    } else {
        next.add(id);
    }

    favorites.value = next;
}
</script>

<template>
    <Head title="线路库" />
    <main
        class="paper-grid min-h-full bg-[#f8f7f0] p-4 sm:p-6 lg:p-8 dark:bg-background"
    >
        <div class="mx-auto max-w-[1480px]">
            <PageIntro
                eyebrow="Route library"
                title="从一条好线路，开始一段好时光"
                description="这里汇集已经完成校内审批的疗休养线路。你可以直接选用并创建团期，也可以提交一份自己的逐日行程。"
            >
                <div class="flex flex-wrap gap-2">
                    <Link
                        v-if="canImport"
                        href="/routes/import"
                        class="inline-flex h-11 items-center gap-2 rounded-full border border-[#b9aa8b] bg-[#fffefa] px-5 text-sm font-semibold text-[#775b3d] transition hover:-translate-y-0.5 hover:bg-[#f6f0e4]"
                    >
                        <FileSpreadsheet class="size-4" /> 工会批量导入
                    </Link>
                    <Link
                        href="/routes/create"
                        class="inline-flex h-11 items-center gap-2 rounded-full bg-[#1b463b] px-5 text-sm font-semibold text-white shadow-[0_10px_24px_rgba(27,70,59,.18)] transition hover:-translate-y-0.5 hover:bg-[#245849]"
                        ><Plus class="size-4" /> 申报新线路</Link
                    >
                </div>
            </PageIntro>

            <section
                class="enter-up mt-8 flex flex-col gap-4 rounded-2xl border border-[#e0ded2] bg-[#fffefa]/85 p-3 md:flex-row md:items-center"
                style="animation-delay: 80ms"
            >
                <label
                    class="flex h-11 flex-1 items-center gap-3 rounded-xl bg-[#f3f1e8] px-4 text-sm text-[#77786f] dark:bg-muted"
                    ><Search class="size-4" /><input
                        v-model="search"
                        class="w-full bg-transparent outline-none placeholder:text-[#aaa89f]"
                        placeholder="搜索目的地、线路名称或特色"
                /></label>
                <div class="flex items-center gap-2 overflow-x-auto px-1">
                    <SlidersHorizontal
                        class="mr-1 size-4 shrink-0 text-[#9a978b]"
                    /><button
                        v-for="item in regions"
                        :key="item"
                        type="button"
                        :class="[
                            'shrink-0 rounded-full px-3.5 py-2 text-xs transition',
                            region === item
                                ? 'bg-[#c56340] text-white'
                                : 'bg-transparent text-[#6e7169] hover:bg-[#eeeade]',
                        ]"
                        @click="region = item"
                    >
                        {{ item }}
                    </button>
                </div>
            </section>

            <div
                class="mt-5 flex items-center justify-between text-xs text-[#89887f]"
            >
                <span>共找到 {{ filteredRoutes.length }} 条已审批线路</span
                ><span>按最近更新排序</span>
            </div>

            <section
                v-if="filteredRoutes.length"
                class="mt-4 grid gap-5 md:grid-cols-2 2xl:grid-cols-3"
            >
                <article
                    v-for="(route, index) in filteredRoutes"
                    :key="route.id"
                    class="enter-up group overflow-hidden rounded-[24px] border border-[#dfded3] bg-[#fffefa] transition duration-300 hover:-translate-y-1 hover:shadow-[0_22px_54px_rgba(37,65,55,.12)] dark:border-border dark:bg-card"
                    :style="{ animationDelay: `${100 + index * 45}ms` }"
                >
                    <RouteArtwork
                        :palette="route.palette"
                        :location="route.location"
                        :accent="route.accent"
                        :image="route.cover"
                        class="h-48"
                    >
                        <button
                            type="button"
                            class="absolute right-4 bottom-4 grid size-9 place-items-center rounded-full bg-[#fffefa]/90 text-[#40584f] shadow-md backdrop-blur-sm transition hover:scale-105"
                            aria-label="收藏线路"
                            @click="toggleFavorite(route.id)"
                        >
                            <Heart
                                :class="[
                                    'size-4',
                                    favorites.has(route.id) &&
                                        'fill-[#c56340] text-[#c56340]',
                                ]"
                            />
                        </button>
                    </RouteArtwork>
                    <div class="p-5">
                        <div class="flex flex-wrap gap-1.5">
                            <span
                                v-if="route.importedByUnion"
                                class="rounded-full bg-[#e3ece6] px-2.5 py-1 text-[10px] font-semibold text-[#356050]"
                            >
                                工会统一导入
                            </span>
                            <span
                                v-for="tag in route.tags"
                                :key="tag"
                                class="rounded-full bg-[#f1efe6] px-2.5 py-1 text-[10px] text-[#74766e] dark:bg-muted"
                                >{{ tag }}</span
                            >
                        </div>
                        <div
                            class="mt-4 inline-flex items-center gap-1.5 rounded-full bg-[#fff4df] px-3 py-1.5 text-[10px] font-semibold text-[#9d5b33]"
                        >
                            <Star
                                :class="[
                                    'size-3.5',
                                    route.rating != null &&
                                        'fill-[#d99a45] text-[#d99a45]',
                                ]"
                            />
                            <template v-if="route.rating != null">
                                {{ route.rating.toFixed(1) }} ·
                                {{ route.reviewCount ?? 0 }} 条评价
                            </template>
                            <template v-else>暂无参团评价</template>
                        </div>
                        <div
                            v-if="route.funding"
                            class="mt-2 ml-2 inline-flex items-center gap-1.5 rounded-full bg-[#e9f1eb] px-3 py-1.5 text-[10px] font-semibold text-[#3c6758]"
                        >
                            <CircleDollarSign class="size-3.5" />
                            工会补助 {{ route.funding.dailySubsidy }} 元/人/天
                        </div>
                        <Link :href="`/routes/${route.id}`">
                            <h2
                                class="font-serif-cn mt-4 text-xl font-semibold text-[#23453b] transition hover:text-[#b45d3d] dark:text-foreground"
                            >
                                {{ route.title }}
                            </h2>
                        </Link>
                        <p
                            class="mt-2 line-clamp-2 text-xs leading-6 text-[#808078]"
                        >
                            {{ route.summary }}
                        </p>
                        <div
                            class="mt-4 flex items-center gap-4 border-t border-[#ece9df] pt-4 text-[11px] text-[#686d65] dark:border-border"
                        >
                            <span class="inline-flex items-center gap-1.5"
                                ><Clock3 class="size-3.5 text-[#b45d3d]" />{{
                                    route.days
                                }}
                                天</span
                            ><span class="inline-flex items-center gap-1.5"
                                ><UsersRound
                                    class="size-3.5 text-[#b45d3d]"
                                />{{ route.people }}</span
                            ><span class="ml-auto"
                                >更新于 {{ route.updatedAt }}</span
                            >
                        </div>
                        <Link
                            :href="`/routes/${route.id}`"
                            class="mt-5 flex items-center justify-between rounded-xl bg-[#f3f1e8] px-4 py-3 text-xs font-semibold text-[#285044] transition group-hover:bg-[#e7eee8] dark:bg-muted"
                            >查看日程并发起组团 <ArrowRight class="size-4"
                        /></Link>
                    </div>
                </article>
            </section>
            <div
                v-else
                class="mt-12 rounded-3xl border border-dashed border-[#d7d4c8] py-20 text-center"
            >
                <p class="font-serif-cn text-xl text-[#35564c]">
                    没有找到匹配的线路
                </p>
                <button
                    class="mt-3 text-xs text-[#b45d3d]"
                    @click="
                        search = '';
                        region = '全部';
                    "
                >
                    清除筛选条件
                </button>
            </div>
        </div>
    </main>
</template>
