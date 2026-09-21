<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowLeft,
    ArrowRight,
    CalendarDays,
    Check,
    CircleDollarSign,
    FileText,
    GripVertical,
    Hotel,
    MapPin,
    Paperclip,
    Plane,
    Plus,
    ShieldCheck,
    Sparkles,
    Trash2,
    UploadCloud,
    Utensils,
    UsersRound,
} from '@lucide/vue';
import { toast } from 'vue-sonner';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: '线路库', href: '/routes' },
            { title: '申报新线路', href: '/routes/create' },
        ],
    },
});

type ItineraryDay = {
    id: number;
    title: string;
    location: string;
    transport: string;
    morning: string;
    afternoon: string;
    evening: string;
    plan: string;
    meals: string;
    stay: string;
    note: string;
};

type PdfDraft = {
    title: string;
    region: string;
    location: string;
    summary: string;
    min_people: number;
    max_people: number;
    departure_city: string;
    return_city: string;
    inbound_transport: string;
    outbound_transport: string;
    highlights: string[];
    experiences: string;
    hotel_standard: string;
    meal_standard: string;
    local_transport: string;
    ticket_standard: string;
    guide_service: string;
    insurance: string;
    value_added: string[];
    self_funded_items: string[];
    notices: string[];
    cover_generated: boolean;
    days: ItineraryDay[];
};

const props = defineProps<{
    pdfDraft?: PdfDraft | null;
    pdfDraftToken?: string | null;
    pdfSourceName?: string | null;
}>();
const draft = props.pdfDraft;
const step = ref(1);
const routeName = ref(draft?.title ?? '');
const routeRegion = ref(draft?.region ?? '其他');
const location = ref(draft?.location ?? '');
const summary = ref(draft?.summary ?? '');
const departureCity = ref(draft?.departure_city ?? '');
const returnCity = ref(draft?.return_city ?? '');
const inboundTransport = ref(draft?.inbound_transport ?? '');
const outboundTransport = ref(draft?.outbound_transport ?? '');
const highlights = ref(draft?.highlights?.join('\n') ?? '');
const experiences = ref(draft?.experiences ?? '');
const hotelStandard = ref(draft?.hotel_standard ?? '');
const mealStandard = ref(draft?.meal_standard ?? '');
const localTransport = ref(draft?.local_transport ?? '');
const ticketStandard = ref(draft?.ticket_standard ?? '');
const guideService = ref(draft?.guide_service ?? '');
const insurance = ref(draft?.insurance ?? '');
const valueAdded = ref(draft?.value_added?.join('\n') ?? '');
const selfFundedItems = ref(
    draft?.self_funded_items?.join('\n') ??
        '往返大交通费用（如机票、高铁票）\n个人消费及方案未列明项目',
);
const notices = ref(draft?.notices?.join('\n') ?? '');
const coverGenerated = ref(draft?.cover_generated ?? false);
const minPeople = ref(draft?.min_people ?? 12);
const maxPeople = ref(draft?.max_people ?? 24);
const days = ref<ItineraryDay[]>(
    draft?.days?.length
        ? draft.days
        : [
              {
                  id: 1,
                  title: '',
                  location: '',
                  transport: '',
                  morning: '',
                  afternoon: '',
                  evening: '',
                  plan: '',
                  meals: '',
                  stay: '',
                  note: '',
              },
              {
                  id: 2,
                  title: '',
                  location: '',
                  transport: '',
                  morning: '',
                  afternoon: '',
                  evening: '',
                  plan: '',
                  meals: '',
                  stay: '',
                  note: '',
              },
          ],
);

function addDay() {
    days.value.push({
        id: Date.now(),
        title: '',
        location: '',
        transport: '',
        morning: '',
        afternoon: '',
        evening: '',
        plan: '',
        meals: '',
        stay: '',
        note: '',
    });
}

function generateCover() {
    if (!routeName.value || !location.value) {
        toast.error('请先填写线路名称和目的地');

        step.value = 1;

        return;
    }

    coverGenerated.value = true;
    toast.success('已根据目的地生成线路封面', {
        description: '提交前仍可重新生成或改为手动上传。',
    });
}

function removeDay(id: number) {
    if (days.value.length <= 1) {
        return;
    }

    days.value = days.value.filter((day) => day.id !== id);
}

function submitRoute() {
    if (!routeName.value || !location.value || !summary.value) {
        toast.error('还有必填信息未完成', {
            description: '请先补充线路名称、目的地与线路简介。',
        });

        step.value = 1;

        return;
    }

    const splitLines = (value: string) =>
        value
            .split(/\r?\n/)
            .map((item) => item.trim())
            .filter(Boolean);

    router.post(
        '/routes',
        {
            title: routeName.value,
            region: routeRegion.value,
            location: location.value,
            summary: summary.value,
            min_people: minPeople.value,
            max_people: maxPeople.value,
            departure_city: departureCity.value,
            return_city: returnCity.value,
            inbound_transport: inboundTransport.value,
            outbound_transport: outboundTransport.value,
            highlights: splitLines(highlights.value),
            experiences: experiences.value,
            hotel_standard: hotelStandard.value,
            meal_standard: mealStandard.value,
            local_transport: localTransport.value,
            ticket_standard: ticketStandard.value,
            guide_service: guideService.value,
            insurance: insurance.value,
            value_added: splitLines(valueAdded.value),
            self_funded_items: splitLines(selfFundedItems.value),
            notices: splitLines(notices.value),
            cover_generated: coverGenerated.value,
            pdf_draft_token: props.pdfDraftToken,
            days: days.value,
        },
        {
            onError: () =>
                toast.error('提交失败', {
                    description: '请检查四个步骤中的必填内容。',
                }),
        },
    );
}
</script>

<template>
    <Head title="申报新线路" />
    <main
        class="paper-grid min-h-full bg-[#f8f7f0] p-4 sm:p-6 lg:p-8 dark:bg-background"
    >
        <div class="mx-auto max-w-5xl">
            <Link
                href="/routes"
                class="inline-flex items-center gap-2 text-xs text-[#687169] hover:text-[#b25c3d]"
                ><ArrowLeft class="size-3.5" /> 返回线路库</Link
            >
            <div
                class="mt-5 flex flex-col justify-between gap-5 md:flex-row md:items-end"
            >
                <div>
                    <p
                        class="text-[10px] font-semibold tracking-[0.2em] text-[#b25c3d] uppercase"
                    >
                        New route
                    </p>
                    <h1
                        class="font-serif-cn mt-2 text-3xl font-semibold text-[#1d4237] md:text-4xl dark:text-foreground"
                    >
                        申报一条新线路
                    </h1>
                    <p class="mt-3 text-sm text-[#7b7c74]">
                        把想去的地方整理成一份清晰的逐日计划，审批通过后即可被全校复用。
                    </p>
                </div>
                <span
                    class="rounded-full border border-[#d7d4c7] bg-[#fffefa] px-4 py-2 text-xs font-semibold text-[#38584e]"
                    >提交后进入真实审批流程</span
                >
            </div>

            <div
                v-if="pdfDraft"
                class="mt-6 flex flex-col gap-3 rounded-2xl border border-[#b9d3c7] bg-[#edf5f0] p-4 sm:flex-row sm:items-center sm:justify-between dark:border-border dark:bg-muted"
            >
                <div class="flex items-start gap-3">
                    <span
                        class="grid size-9 shrink-0 place-items-center rounded-full bg-[#28604f] text-white"
                    >
                        <FileText class="size-4" />
                    </span>
                    <div>
                        <p class="text-sm font-semibold text-[#285244]">
                            PDF 已解析并填入草稿
                        </p>
                        <p class="mt-1 text-xs leading-5 text-[#718078]">
                            {{ pdfSourceName }} ·
                            请重点核对逐日行程、人数与费用说明
                        </p>
                    </div>
                </div>
                <span
                    class="shrink-0 rounded-full bg-white px-4 py-2 text-[10px] font-semibold text-[#35705d] shadow-sm dark:bg-card"
                >
                    原文件将随线路保留
                </span>
            </div>

            <div class="mt-8 grid gap-8 lg:grid-cols-[190px_1fr]">
                <aside class="lg:sticky lg:top-6 lg:self-start">
                    <ol class="space-y-2">
                        <li
                            v-for="item in [
                                { n: 1, t: '基本信息', d: '名称与适用范围' },
                                { n: 2, t: '逐日行程', d: '每日地点与安排' },
                                { n: 3, t: '服务标准', d: '住宿与保障项目' },
                                {
                                    n: 4,
                                    t: '材料与封面',
                                    d: '附件、配图与确认',
                                },
                            ]"
                            :key="item.n"
                        >
                            <button
                                type="button"
                                :class="[
                                    'flex w-full items-center gap-3 rounded-2xl p-3 text-left transition',
                                    step === item.n
                                        ? 'bg-[#1c493d] text-white shadow-lg'
                                        : 'text-[#777a72] hover:bg-[#eeeae0]',
                                ]"
                                @click="step = item.n"
                            >
                                <span
                                    :class="[
                                        'grid size-8 shrink-0 place-items-center rounded-full text-xs font-semibold',
                                        step === item.n
                                            ? 'bg-[#e9bd7b] text-[#1c493d]'
                                            : 'bg-[#e7e4da] text-[#6d6e66]',
                                    ]"
                                    >{{ item.n }}</span
                                ><span
                                    ><span
                                        class="block text-xs font-semibold"
                                        >{{ item.t }}</span
                                    ><span
                                        :class="[
                                            'mt-0.5 block text-[10px]',
                                            step === item.n
                                                ? 'text-white/55'
                                                : 'text-[#aaa89e]',
                                        ]"
                                        >{{ item.d }}</span
                                    ></span
                                >
                            </button>
                        </li>
                    </ol>
                    <div
                        class="mt-5 rounded-2xl border border-[#dedbce] p-4 text-[10px] leading-5 text-[#83837b]"
                    >
                        <div
                            class="mb-2 flex items-center gap-2 font-semibold text-[#4c635b]"
                        >
                            <FileText class="size-3.5" /> 审批提示
                        </div>
                        提交后将依次进入二级单位初审和校工会终审。
                    </div>
                </aside>

                <section
                    class="rounded-[26px] border border-[#dfdcd0] bg-[#fffefa] p-5 shadow-[0_18px_48px_rgba(53,64,54,.06)] md:p-8 dark:border-border dark:bg-card"
                >
                    <div v-if="step === 1" class="space-y-6">
                        <div>
                            <p
                                class="font-serif-cn text-xl font-semibold text-[#294a40]"
                            >
                                线路基本信息
                            </p>
                            <p class="mt-1 text-xs text-[#99978d]">
                                带 * 的内容将在公开线路详情中展示
                            </p>
                        </div>
                        <label class="block"
                            ><span class="mb-2 block text-xs font-semibold"
                                >线路名称 *</span
                            ><input
                                v-model="routeName"
                                class="h-11 w-full rounded-xl border border-[#dcd9cd] bg-[#faf9f3] px-4 text-sm transition outline-none focus:border-[#477467] focus:ring-3 focus:ring-[#477467]/10 dark:bg-background"
                                placeholder="如：松风入海 · 舟山慢岛五日"
                        /></label>
                        <div class="grid gap-5 md:grid-cols-2">
                            <label class="block"
                                ><span class="mb-2 block text-xs font-semibold"
                                    >主要目的地 *</span
                                >
                                <div class="relative">
                                    <MapPin
                                        class="absolute top-3.5 left-3.5 size-4 text-[#b15d3e]"
                                    /><input
                                        v-model="location"
                                        class="h-11 w-full rounded-xl border border-[#dcd9cd] bg-[#faf9f3] pr-4 pl-10 text-sm outline-none focus:border-[#477467] dark:bg-background"
                                        placeholder="省 / 市 / 主要地点"
                                    /></div></label
                            ><label class="block"
                                ><span class="mb-2 block text-xs font-semibold"
                                    >建议交通方式 *</span
                                ><select
                                    class="h-11 w-full rounded-xl border border-[#dcd9cd] bg-[#faf9f3] px-4 text-sm outline-none dark:bg-background"
                                >
                                    <option>高铁 + 当地大巴</option>
                                    <option>飞机 + 当地大巴</option>
                                    <option>校内统一大巴</option>
                                </select></label
                            >
                        </div>
                        <div class="grid gap-5 md:grid-cols-2">
                            <label class="block"
                                ><span class="mb-2 block text-xs font-semibold"
                                    >出发城市 *</span
                                ><input
                                    v-model="departureCity"
                                    class="h-11 w-full rounded-xl border border-[#dcd9cd] bg-[#faf9f3] px-4 text-sm outline-none focus:border-[#477467] dark:bg-background"
                                    placeholder="如：杭州"
                            /></label>
                            <label class="block"
                                ><span class="mb-2 block text-xs font-semibold"
                                    >返程城市 *</span
                                ><input
                                    v-model="returnCity"
                                    class="h-11 w-full rounded-xl border border-[#dcd9cd] bg-[#faf9f3] px-4 text-sm outline-none focus:border-[#477467] dark:bg-background"
                                    placeholder="如：杭州"
                            /></label>
                        </div>
                        <div class="grid gap-5 md:grid-cols-2">
                            <label class="block"
                                ><span
                                    class="mb-2 flex items-center gap-2 text-xs font-semibold"
                                    ><Plane class="size-3.5 text-[#b15d3e]" />
                                    去程交通参考</span
                                ><input
                                    v-model="inboundTransport"
                                    class="h-11 w-full rounded-xl border border-[#dcd9cd] bg-[#faf9f3] px-4 text-sm outline-none dark:bg-background"
                                    placeholder="航班/车次、参考时刻"
                            /></label>
                            <label class="block"
                                ><span
                                    class="mb-2 flex items-center gap-2 text-xs font-semibold"
                                    ><Plane
                                        class="size-3.5 rotate-180 text-[#b15d3e]"
                                    />
                                    返程交通参考</span
                                ><input
                                    v-model="outboundTransport"
                                    class="h-11 w-full rounded-xl border border-[#dcd9cd] bg-[#faf9f3] px-4 text-sm outline-none dark:bg-background"
                                    placeholder="航班/车次、参考时刻"
                            /></label>
                        </div>
                        <label class="block"
                            ><span class="mb-2 block text-xs font-semibold"
                                >线路简介 *</span
                            ><textarea
                                v-model="summary"
                                rows="4"
                                class="w-full resize-none rounded-xl border border-[#dcd9cd] bg-[#faf9f3] p-4 text-sm leading-6 outline-none focus:border-[#477467] dark:bg-background"
                                placeholder="用 50—500 字介绍线路特色、节奏与适宜人群"
                            />
                        </label>
                        <div class="grid gap-5 md:grid-cols-2">
                            <label class="block"
                                ><span class="mb-2 block text-xs font-semibold"
                                    >线路亮点 *</span
                                ><textarea
                                    v-model="highlights"
                                    rows="4"
                                    class="w-full resize-none rounded-xl border border-[#dcd9cd] bg-[#faf9f3] p-4 text-sm leading-6 outline-none dark:bg-background"
                                    placeholder="每行一个亮点，如核心景区、文化主题、疗休养特色"
                                />
                            </label>
                            <label class="block"
                                ><span class="mb-2 block text-xs font-semibold"
                                    >特色体验</span
                                ><textarea
                                    v-model="experiences"
                                    rows="4"
                                    class="w-full resize-none rounded-xl border border-[#dcd9cd] bg-[#faf9f3] p-4 text-sm leading-6 outline-none dark:bg-background"
                                    placeholder="如民俗体验、特色餐饮、非遗活动等"
                                />
                            </label>
                        </div>
                        <div class="grid gap-5 md:grid-cols-2">
                            <label class="block"
                                ><span
                                    class="mb-2 flex items-center gap-2 text-xs font-semibold"
                                    ><UsersRound
                                        class="size-3.5 text-[#b15d3e]"
                                    />
                                    适用人数 *</span
                                >
                                <div class="flex items-center gap-2">
                                    <input
                                        v-model="minPeople"
                                        type="number"
                                        class="h-11 min-w-0 flex-1 rounded-xl border border-[#dcd9cd] bg-[#faf9f3] px-4 text-sm dark:bg-background"
                                    /><span class="text-[#aaa89e]">至</span
                                    ><input
                                        v-model="maxPeople"
                                        type="number"
                                        class="h-11 min-w-0 flex-1 rounded-xl border border-[#dcd9cd] bg-[#faf9f3] px-4 text-sm dark:bg-background"
                                    /></div></label
                            ><label class="block"
                                ><span
                                    class="mb-2 flex items-center gap-2 text-xs font-semibold"
                                    ><CalendarDays
                                        class="size-3.5 text-[#b15d3e]"
                                    />
                                    建议天数</span
                                >
                                <div
                                    class="flex h-11 items-center rounded-xl border border-[#dcd9cd] bg-[#f4f1e8] px-4 text-sm text-[#666a63] dark:bg-muted"
                                >
                                    {{ days.length }} 天（与逐日行程同步）
                                </div></label
                            >
                        </div>
                    </div>

                    <div v-else-if="step === 2" class="space-y-5">
                        <div class="flex items-end justify-between">
                            <div>
                                <p
                                    class="font-serif-cn text-xl font-semibold text-[#294a40]"
                                >
                                    逐日行程
                                </p>
                                <p class="mt-1 text-xs text-[#99978d]">
                                    逐天说明地点、活动安排和必要提醒
                                </p>
                            </div>
                            <button
                                type="button"
                                class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#b15d3e]"
                                @click="addDay"
                            >
                                <Plus class="size-3.5" /> 增加一天
                            </button>
                        </div>
                        <article
                            v-for="(day, index) in days"
                            :key="day.id"
                            class="rounded-2xl border border-[#dfdcd0] p-4 md:p-5"
                        >
                            <div class="mb-4 flex items-center gap-3">
                                <GripVertical
                                    class="size-4 text-[#b7b4aa]"
                                /><span
                                    class="font-serif-cn grid size-8 place-items-center rounded-full bg-[#1e4c40] text-sm font-semibold text-white"
                                    >{{ index + 1 }}</span
                                ><span class="text-xs font-semibold"
                                    >第 {{ index + 1 }} 天</span
                                ><button
                                    type="button"
                                    class="ml-auto text-[#b7b4aa] hover:text-[#b15d3e]"
                                    aria-label="删除该日"
                                    @click="removeDay(day.id)"
                                >
                                    <Trash2 class="size-4" />
                                </button>
                            </div>
                            <div class="grid gap-3 md:grid-cols-2">
                                <input
                                    v-model="day.title"
                                    class="h-10 rounded-xl bg-[#f4f2e9] px-3 text-xs outline-none dark:bg-muted"
                                    placeholder="当日主题，如：抵达舟山 · 海岛初见"
                                /><input
                                    v-model="day.location"
                                    class="h-10 rounded-xl bg-[#f4f2e9] px-3 text-xs outline-none dark:bg-muted"
                                    placeholder="地点"
                                />
                            </div>
                            <input
                                v-model="day.transport"
                                class="mt-3 h-10 w-full rounded-xl bg-[#f4f2e9] px-3 text-xs outline-none dark:bg-muted"
                                placeholder="当日交通段、参考时长或航班/车次"
                            />
                            <div class="mt-3 grid gap-3 md:grid-cols-3">
                                <textarea
                                    v-model="day.morning"
                                    rows="3"
                                    class="resize-none rounded-xl bg-[#f4f2e9] p-3 text-xs leading-5 outline-none dark:bg-muted"
                                    placeholder="上午安排"
                                />
                                <textarea
                                    v-model="day.afternoon"
                                    rows="3"
                                    class="resize-none rounded-xl bg-[#f4f2e9] p-3 text-xs leading-5 outline-none dark:bg-muted"
                                    placeholder="下午安排"
                                />
                                <textarea
                                    v-model="day.evening"
                                    rows="3"
                                    class="resize-none rounded-xl bg-[#f4f2e9] p-3 text-xs leading-5 outline-none dark:bg-muted"
                                    placeholder="晚间安排"
                                />
                            </div>
                            <textarea
                                v-model="day.plan"
                                rows="2"
                                class="mt-3 w-full resize-none rounded-xl bg-[#f4f2e9] p-3 text-xs leading-6 outline-none dark:bg-muted"
                                placeholder="景点介绍、体验说明或当日整体补充"
                            />
                            <div class="mt-3 grid gap-3 md:grid-cols-2">
                                <label class="relative"
                                    ><Utensils
                                        class="absolute top-3 left-3 size-3.5 text-[#b15d3e]" /><input
                                        v-model="day.meals"
                                        class="h-10 w-full rounded-xl bg-[#f4f2e9] pr-3 pl-9 text-xs outline-none dark:bg-muted"
                                        placeholder="用餐，如：含早、中、晚餐"
                                /></label>
                                <label class="relative"
                                    ><Hotel
                                        class="absolute top-3 left-3 size-3.5 text-[#b15d3e]" /><input
                                        v-model="day.stay"
                                        class="h-10 w-full rounded-xl bg-[#f4f2e9] pr-3 pl-9 text-xs outline-none dark:bg-muted"
                                        placeholder="住宿城市及标准"
                                /></label>
                            </div>
                            <input
                                v-model="day.note"
                                class="mt-3 h-10 w-full rounded-xl bg-[#f4f2e9] px-3 text-xs outline-none dark:bg-muted"
                                placeholder="当日安全提示或可调整事项"
                            />
                        </article>
                        <button
                            type="button"
                            class="flex w-full items-center justify-center gap-2 rounded-2xl border border-dashed border-[#cecabc] py-4 text-xs text-[#6f776f] hover:border-[#8ba094] hover:bg-[#f6f5ee]"
                            @click="addDay"
                        >
                            <Plus class="size-4" /> 添加第
                            {{ days.length + 1 }} 天行程
                        </button>
                    </div>

                    <div v-else-if="step === 3" class="space-y-6">
                        <div>
                            <p
                                class="font-serif-cn text-xl font-semibold text-[#294a40]"
                            >
                                接待与服务标准
                            </p>
                            <p class="mt-1 text-xs text-[#99978d]">
                                明确方案实际包含的住宿、餐饮、交通和保障内容
                            </p>
                        </div>
                        <div class="grid gap-5 md:grid-cols-2">
                            <label class="block"
                                ><span
                                    class="mb-2 flex items-center gap-2 text-xs font-semibold"
                                    ><Hotel class="size-3.5 text-[#b15d3e]" />
                                    住宿标准 *</span
                                ><textarea
                                    v-model="hotelStandard"
                                    rows="4"
                                    class="w-full resize-none rounded-xl border border-[#dcd9cd] bg-[#faf9f3] p-4 text-sm leading-6 outline-none dark:bg-background"
                                    placeholder="酒店等级、房型、参考酒店及同级替代原则"
                                />
                            </label>
                            <label class="block"
                                ><span
                                    class="mb-2 flex items-center gap-2 text-xs font-semibold"
                                    ><Utensils
                                        class="size-3.5 text-[#b15d3e]"
                                    />
                                    用餐标准 *</span
                                ><textarea
                                    v-model="mealStandard"
                                    rows="4"
                                    class="w-full resize-none rounded-xl border border-[#dcd9cd] bg-[#faf9f3] p-4 text-sm leading-6 outline-none dark:bg-background"
                                    placeholder="早餐和正餐次数、特色餐及特殊饮食说明"
                                />
                            </label>
                        </div>
                        <div class="grid gap-5 md:grid-cols-2">
                            <label class="block"
                                ><span class="mb-2 block text-xs font-semibold"
                                    >当地交通 *</span
                                ><textarea
                                    v-model="localTransport"
                                    rows="3"
                                    class="w-full resize-none rounded-xl border border-[#dcd9cd] bg-[#faf9f3] p-4 text-sm leading-6 outline-none dark:bg-background"
                                    placeholder="车辆类型、接送范围、景区换乘等"
                                />
                            </label>
                            <label class="block"
                                ><span class="mb-2 block text-xs font-semibold"
                                    >门票范围 *</span
                                ><textarea
                                    v-model="ticketStandard"
                                    rows="3"
                                    class="w-full resize-none rounded-xl border border-[#dcd9cd] bg-[#faf9f3] p-4 text-sm leading-6 outline-none dark:bg-background"
                                    placeholder="包含的景点首道门票、景交车或体验项目"
                                />
                            </label>
                        </div>
                        <div class="grid gap-5 md:grid-cols-2">
                            <label class="block"
                                ><span class="mb-2 block text-xs font-semibold"
                                    >导游与领队服务 *</span
                                ><textarea
                                    v-model="guideService"
                                    rows="3"
                                    class="w-full resize-none rounded-xl border border-[#dcd9cd] bg-[#faf9f3] p-4 text-sm leading-6 outline-none dark:bg-background"
                                    placeholder="导游语言、讲解范围及领队安排"
                                />
                            </label>
                            <label class="block"
                                ><span
                                    class="mb-2 flex items-center gap-2 text-xs font-semibold"
                                    ><ShieldCheck
                                        class="size-3.5 text-[#b15d3e]"
                                    />
                                    保险与安全保障 *</span
                                ><textarea
                                    v-model="insurance"
                                    rows="3"
                                    class="w-full resize-none rounded-xl border border-[#dcd9cd] bg-[#faf9f3] p-4 text-sm leading-6 outline-none dark:bg-background"
                                    placeholder="保险类型、保障范围及应急支持"
                                />
                            </label>
                        </div>
                        <label class="block"
                            ><span class="mb-2 block text-xs font-semibold"
                                >增值服务</span
                            ><textarea
                                v-model="valueAdded"
                                rows="4"
                                class="w-full resize-none rounded-xl border border-[#dcd9cd] bg-[#faf9f3] p-4 text-sm leading-6 outline-none dark:bg-background"
                                placeholder="每行一项，如饮用水、旅行用品、生日关怀、不安排购物等"
                            />
                        </label>
                        <div
                            class="overflow-hidden rounded-2xl border border-[#e3c58f] bg-[#fff8e9]"
                        >
                            <div
                                class="flex flex-col gap-3 border-b border-[#ead8b6] bg-[#f7ead1] p-4 sm:flex-row sm:items-center sm:justify-between"
                            >
                                <div class="flex items-center gap-3">
                                    <span
                                        class="grid size-10 place-items-center rounded-full bg-[#a9613f] text-white"
                                    >
                                        <CircleDollarSign class="size-5" />
                                    </span>
                                    <div>
                                        <p
                                            class="text-sm font-semibold text-[#70432d]"
                                        >
                                            工会疗休养经费标准
                                        </p>
                                        <p
                                            class="mt-1 text-[10px] text-[#98735d]"
                                        >
                                            统一按实际行程天数计算
                                        </p>
                                    </div>
                                </div>
                                <p
                                    class="font-serif-cn text-2xl font-semibold text-[#a45435]"
                                >
                                    500 元
                                    <span class="text-xs font-normal"
                                        >/ 人 / 天</span
                                    >
                                </p>
                            </div>
                            <label class="block p-4">
                                <span
                                    class="mb-2 flex items-center gap-2 text-xs font-semibold text-[#70432d]"
                                >
                                    <Plane class="size-3.5" />
                                    个人自理费用项目 *
                                </span>
                                <textarea
                                    v-model="selfFundedItems"
                                    required
                                    rows="4"
                                    class="w-full resize-none rounded-xl border border-[#dfcaa3] bg-white/75 p-4 text-sm leading-6 outline-none focus:border-[#b56a45]"
                                    placeholder="每行一项，如往返机票、高铁票、机场接送、个人消费等"
                                />
                                <p
                                    class="mt-2 text-[10px] leading-5 text-[#98735d]"
                                >
                                    请明确列出不由工会经费承担、需参团人员自行支付的项目，报名页面将直接展示。
                                </p>
                            </label>
                        </div>
                        <label class="block"
                            ><span class="mb-2 block text-xs font-semibold"
                                >统一注意事项 *</span
                            ><textarea
                                v-model="notices"
                                rows="5"
                                class="w-full resize-none rounded-xl border border-[#dcd9cd] bg-[#faf9f3] p-4 text-sm leading-6 outline-none dark:bg-background"
                                placeholder="证件、天气、穿着、健康申报、常备药品及不可抗力调整说明"
                            />
                        </label>
                        <div
                            class="rounded-2xl bg-[#f2eee2] p-4 text-[11px] leading-6 text-[#6f736b]"
                        >
                            系统仅展示经费政策与自理项目，不处理付款和退款；具体金额、出票及结算方式由团长另行通知。
                        </div>
                    </div>

                    <div v-else class="space-y-6">
                        <div>
                            <p
                                class="font-serif-cn text-xl font-semibold text-[#294a40]"
                            >
                                材料与提交
                            </p>
                            <p class="mt-1 text-xs text-[#99978d]">
                                上传现有方案文档，确认信息后进入审批
                            </p>
                        </div>
                        <div
                            class="overflow-hidden rounded-2xl border border-[#dfdcd0] dark:border-border"
                        >
                            <div
                                class="flex flex-col justify-between gap-4 border-b border-[#e4e0d5] p-5 sm:flex-row sm:items-center dark:border-border"
                            >
                                <div>
                                    <div
                                        class="flex items-center gap-2 text-sm font-semibold text-[#315348]"
                                    >
                                        <Sparkles
                                            class="size-4 text-[#b15d3e]"
                                        />
                                        线路首页智能配图
                                    </div>
                                    <p
                                        class="mt-1 text-[10px] leading-5 text-[#919087]"
                                    >
                                        根据线路名称、目的地和亮点生成无文字封面，可重新生成或手动上传。
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    class="inline-flex h-9 shrink-0 items-center justify-center gap-2 rounded-full bg-[#1d4b3e] px-4 text-xs font-semibold text-white"
                                    @click="generateCover"
                                >
                                    <Sparkles class="size-3.5" />
                                    {{
                                        coverGenerated
                                            ? '重新生成封面'
                                            : '自动生成封面'
                                    }}
                                </button>
                            </div>
                            <div
                                v-if="coverGenerated"
                                class="relative aspect-[16/7] overflow-hidden"
                            >
                                <img
                                    src="/images/retreat/changchun-changbaishan-yanji-cover.png"
                                    alt="自动生成的线路封面预览"
                                    class="size-full object-cover"
                                />
                                <div
                                    class="absolute inset-0 bg-gradient-to-t from-[#132f29]/65 via-transparent to-transparent"
                                />
                                <div
                                    class="absolute right-5 bottom-4 left-5 text-white"
                                >
                                    <p class="text-[10px] text-white/65">
                                        自动配图预览
                                    </p>
                                    <p
                                        class="font-serif-cn mt-1 text-lg font-semibold"
                                    >
                                        {{ routeName || '线路名称' }}
                                    </p>
                                </div>
                            </div>
                            <div
                                v-else
                                class="grid aspect-[16/5] place-items-center bg-[#f3f1e8] text-center dark:bg-muted"
                            >
                                <div>
                                    <Sparkles
                                        class="mx-auto size-7 text-[#a8aa9f]"
                                    />
                                    <p class="mt-2 text-[10px] text-[#96958b]">
                                        尚未生成线路封面
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div
                            v-if="pdfSourceName"
                            class="rounded-2xl border border-[#c8dbd2] bg-[#eef5f1] px-6 py-5 dark:border-border dark:bg-muted"
                        >
                            <div class="flex items-center gap-3">
                                <span
                                    class="grid size-10 place-items-center rounded-full bg-[#2c6252] text-white"
                                >
                                    <FileText class="size-4" />
                                </span>
                                <div class="min-w-0">
                                    <p
                                        class="truncate text-sm font-semibold text-[#315348]"
                                    >
                                        {{ pdfSourceName }}
                                    </p>
                                    <p class="mt-1 text-[10px] text-[#758079]">
                                        已完成文字解析；提交线路后可在详情页下载原始方案
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div
                            v-else
                            class="rounded-2xl border border-dashed border-[#cfcbbc] bg-[#f7f5ed] px-6 py-10 text-center dark:bg-muted"
                        >
                            <UploadCloud
                                class="mx-auto size-8 text-[#b15d3e]"
                            />
                            <p
                                class="mt-3 text-sm font-semibold text-[#39564d]"
                            >
                                拖放或点击上传线路材料
                            </p>
                            <p class="mt-2 text-[10px] text-[#99978d]">
                                支持 PDF、DOC、DOCX，单个文件不超过 20MB
                            </p>
                            <button
                                type="button"
                                class="mt-4 inline-flex h-9 items-center gap-2 rounded-full border border-[#d4d0c3] bg-white px-4 text-xs dark:bg-card"
                            >
                                <Paperclip class="size-3.5" /> 选择文件
                            </button>
                        </div>
                        <div class="rounded-2xl bg-[#edf2ed] p-5 dark:bg-muted">
                            <div class="flex items-start gap-3">
                                <div
                                    class="grid size-8 shrink-0 place-items-center rounded-full bg-[#2c6252] text-white"
                                >
                                    <Check class="size-4" />
                                </div>
                                <div>
                                    <p
                                        class="text-sm font-semibold text-[#315348]"
                                    >
                                        提交前请确认
                                    </p>
                                    <p
                                        class="mt-1 text-xs leading-6 text-[#747b74]"
                                    >
                                        线路内容真实完整，逐日行程与附件保持一致；通过审批后，该版本可被其他教职工直接用于组团。
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        class="mt-8 flex items-center justify-between border-t border-[#e6e2d7] pt-5 dark:border-border"
                    >
                        <button
                            type="button"
                            :class="[
                                'inline-flex h-10 items-center gap-2 rounded-full px-4 text-xs font-semibold',
                                step === 1
                                    ? 'invisible'
                                    : 'border border-[#d6d2c5]',
                            ]"
                            @click="step--"
                        >
                            <ArrowLeft class="size-3.5" /> 上一步</button
                        ><button
                            v-if="step < 4"
                            type="button"
                            class="inline-flex h-10 items-center gap-2 rounded-full bg-[#1d4a3e] px-5 text-xs font-semibold text-white"
                            @click="step++"
                        >
                            下一步 <ArrowRight class="size-3.5" /></button
                        ><button
                            v-else
                            type="button"
                            class="inline-flex h-10 items-center gap-2 rounded-full bg-[#c46240] px-5 text-xs font-semibold text-white"
                            @click="submitRoute"
                        >
                            提交审批 <ArrowRight class="size-3.5" />
                        </button>
                    </div>
                </section>
            </div>
        </div>
    </main>
</template>
