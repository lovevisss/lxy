<script setup lang="ts">
import { onBeforeUnmount, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowLeft,
    ArrowRight,
    BadgeCheck,
    CalendarDays,
    CircleDollarSign,
    Download,
    FileText,
    MapPin,
    Plane,
    QrCode,
    ShieldCheck,
    Smartphone,
    UserRoundCheck,
    UsersRound,
} from '@lucide/vue';
import { toast } from 'vue-sonner';
import RouteArtwork from '@/components/retreat/RouteArtwork.vue';
import { routeById } from '@/data/retreat';
import type { RetreatGroup, RetreatRoute } from '@/data/retreat';

const props = defineProps<{
    routeId: number;
    routeRecord?: RetreatRoute;
    groupRecord?: RetreatGroup;
    editMode?: boolean;
}>();
const route = props.routeRecord ?? routeById(props.routeId || 1);
const groupName = ref(
    props.groupRecord?.title ??
        `${route.location.split('·').pop()?.trim() ?? route.location}秋季疗休养团`,
);
const contactMobile = ref(props.groupRecord?.contactMobile ?? '');
const departure = ref(props.groupRecord?.departureDate ?? '');
const returnDate = ref(props.groupRecord?.returnDate ?? '');
const deadline = ref(props.groupRecord?.applicationDeadline ?? '');
const minPeople = ref(
    props.groupRecord?.min ?? Number(route.people.match(/\d+/)?.[0] ?? 10),
);
const maxPeople = ref(
    props.groupRecord?.capacity ??
        Number(route.people.match(/\d+(?=\s*人)/)?.[0] ?? 20),
);
const approvalMode = ref<'automatic' | 'manual'>(
    props.groupRecord?.approvalMode ?? 'manual',
);
const meetingInfo = ref(props.groupRecord?.meetingInfo ?? '');
const notes = ref(props.groupRecord?.notes ?? '');
const attachment = ref<File | null>(null);
const attachmentName = ref(props.groupRecord?.attachmentName ?? '');
const wechatQrCode = ref<File | null>(null);
const wechatQrCodeName = ref(props.groupRecord?.wechatQrCodeName ?? '');
const wechatQrCodePreview = ref(props.groupRecord?.wechatQrCodeUrl ?? '');
let localQrCodePreview = '';

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

    if (deadline.value >= departure.value) {
        toast.error('报名截止日期必须早于出发日期');

        return;
    }

    if (returnDate.value < departure.value) {
        toast.error('返程日期不能早于出发日期');

        return;
    }

    router.post(
        props.editMode && props.groupRecord
            ? `/groups/${props.groupRecord.id}`
            : '/groups',
        {
            ...(props.editMode ? { _method: 'put' } : {}),
            retreat_route_id: route.id,
            title: groupName.value,
            departure_date: departure.value,
            return_date: returnDate.value,
            application_deadline: deadline.value,
            min_people: minPeople.value,
            max_people: maxPeople.value,
            approval_mode: approvalMode.value,
            meeting_info: meetingInfo.value,
            notes: notes.value,
            contact_mobile: contactMobile.value,
            attachment: attachment.value,
            wechat_qr_code: wechatQrCode.value,
        },
        {
            forceFormData: true,
            onError: (errors) =>
                toast.error(props.editMode ? '保存失败' : '发布失败', {
                    description:
                        Object.values(errors)[0] ??
                        '请检查日期和人数是否符合线路审批范围。',
                }),
        },
    );
}

function selectAttachment(event: Event) {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;

    if (file && file.type !== 'application/pdf') {
        toast.error('只能上传 PDF 文件');
        (event.target as HTMLInputElement).value = '';

        return;
    }

    attachment.value = file;
    attachmentName.value =
        file?.name ?? props.groupRecord?.attachmentName ?? '';
}

function selectWechatQrCode(event: Event) {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;
    const supportedTypes = ['image/jpeg', 'image/png', 'image/webp'];

    if (file && !supportedTypes.includes(file.type)) {
        toast.error('二维码仅支持 JPG、PNG 或 WebP 图片');
        input.value = '';

        return;
    }

    if (file && file.size > 5 * 1024 * 1024) {
        toast.error('二维码图片不能超过 5MB');
        input.value = '';

        return;
    }

    if (localQrCodePreview) {
        URL.revokeObjectURL(localQrCodePreview);
        localQrCodePreview = '';
    }

    wechatQrCode.value = file;
    wechatQrCodeName.value =
        file?.name ?? props.groupRecord?.wechatQrCodeName ?? '';
    localQrCodePreview = file ? URL.createObjectURL(file) : '';
    wechatQrCodePreview.value =
        localQrCodePreview || props.groupRecord?.wechatQrCodeUrl || '';
}

onBeforeUnmount(() => {
    if (localQrCodePreview) {
        URL.revokeObjectURL(localQrCodePreview);
    }
});
</script>

<template>
    <Head :title="editMode ? '编辑组团' : '创建组团'" />
    <main
        class="paper-grid min-h-full bg-[#f8f7f0] p-4 sm:p-6 lg:p-8 dark:bg-background"
    >
        <div class="mx-auto max-w-5xl">
            <Link
                :href="
                    editMode && groupRecord
                        ? `/groups/${groupRecord.id}`
                        : `/routes/${route.id}`
                "
                class="inline-flex items-center gap-2 text-xs text-[#687169] hover:text-[#b25c3d]"
                ><ArrowLeft class="size-3.5" />
                {{ editMode ? '返回组团详情' : '返回线路详情' }}</Link
            >
            <div class="mt-5">
                <p
                    class="text-[10px] font-semibold tracking-[.2em] text-[#b25c3d] uppercase"
                >
                    {{ editMode ? 'Edit group activity' : 'Create a group' }}
                </p>
                <h1
                    class="font-serif-cn mt-2 text-3xl font-semibold text-[#1d4237] md:text-4xl dark:text-foreground"
                >
                    {{ editMode ? '编辑组团活动' : '为这条线路创建团期' }}
                </h1>
                <p class="mt-3 text-sm text-[#7b7c74]">
                    {{
                        editMode
                            ? '更新团期、集合信息、微信群二维码与活动方案，保存后立即对报名成员生效。'
                            : '线路已经完成审批，填写日期和人数后即可直接开放报名。'
                    }}
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
                        <div
                            v-if="route.funding"
                            class="mt-3 rounded-xl border border-[#e2c78f] bg-[#fff8e9] p-4"
                        >
                            <p
                                class="flex items-center gap-2 text-[10px] font-semibold text-[#7b4932]"
                            >
                                <CircleDollarSign class="size-4" />工会补助
                            </p>
                            <p
                                class="font-serif-cn mt-2 text-lg font-semibold text-[#a45435]"
                            >
                                {{ route.funding.dailySubsidy }} 元/人/天
                            </p>
                            <p
                                class="mt-2 flex items-start gap-1.5 text-[10px] leading-5 text-[#8f6c56]"
                            >
                                <Plane class="mt-0.5 size-3 shrink-0" />
                                自理：{{
                                    route.funding.selfFundedItems.join('、')
                                }}
                            </p>
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
                        <label class="block"
                            ><span
                                class="mb-2 flex items-center gap-2 text-xs font-semibold"
                                ><Smartphone class="size-3.5 text-[#b15d3d]" />
                                团长联系手机号 *</span
                            ><input
                                v-model="contactMobile"
                                type="tel"
                                :required="!editMode"
                                maxlength="11"
                                pattern="1[3-9][0-9]{9}"
                                class="h-11 w-full rounded-xl border border-[#dad7ca] bg-[#faf9f3] px-4 text-sm outline-none focus:border-[#477467] dark:bg-background"
                                placeholder="用于接收成团及状态提醒"
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
                        <div>
                            <div class="flex items-end justify-between gap-3">
                                <div>
                                    <span class="block text-xs font-semibold">
                                        报名确认方式 *
                                    </span>
                                    <p class="mt-1 text-[10px] text-[#99978e]">
                                        发布后会在组团详情和报名入口向教职工明确展示
                                    </p>
                                </div>
                                <span
                                    v-if="editMode"
                                    class="text-[10px] text-[#a16a4f]"
                                >
                                    已发布团队不可切换
                                </span>
                            </div>
                            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                <button
                                    type="button"
                                    :disabled="editMode"
                                    :class="[
                                        'rounded-2xl border p-4 text-left transition disabled:cursor-not-allowed',
                                        approvalMode === 'automatic'
                                            ? 'border-[#4f786a] bg-[#edf3ee] shadow-[0_8px_24px_rgba(52,92,77,.08)]'
                                            : 'border-[#ddd8cb] bg-[#faf9f3] hover:border-[#8aa297]',
                                    ]"
                                    @click="approvalMode = 'automatic'"
                                >
                                    <div class="flex items-center gap-3">
                                        <span
                                            class="grid size-9 place-items-center rounded-full bg-[#dcebe2] text-[#32604f]"
                                        >
                                            <UserRoundCheck class="size-4" />
                                        </span>
                                        <div>
                                            <p
                                                class="text-sm font-semibold text-[#315348]"
                                            >
                                                自动参团
                                            </p>
                                            <p
                                                class="mt-1 text-[10px] text-[#7e857f]"
                                            >
                                                名额充足时报名立即通过
                                            </p>
                                        </div>
                                    </div>
                                </button>
                                <button
                                    type="button"
                                    :disabled="editMode"
                                    :class="[
                                        'rounded-2xl border p-4 text-left transition disabled:cursor-not-allowed',
                                        approvalMode === 'manual'
                                            ? 'border-[#b87959] bg-[#fff4ed] shadow-[0_8px_24px_rgba(158,91,56,.08)]'
                                            : 'border-[#ddd8cb] bg-[#faf9f3] hover:border-[#c6977e]',
                                    ]"
                                    @click="approvalMode = 'manual'"
                                >
                                    <div class="flex items-center gap-3">
                                        <span
                                            class="grid size-9 place-items-center rounded-full bg-[#f3dfd3] text-[#a25b3e]"
                                        >
                                            <ShieldCheck class="size-4" />
                                        </span>
                                        <div>
                                            <p
                                                class="text-sm font-semibold text-[#704632]"
                                            >
                                                团长审核
                                            </p>
                                            <p
                                                class="mt-1 text-[10px] text-[#8d786d]"
                                            >
                                                报名后由团长逐项确认
                                            </p>
                                        </div>
                                    </div>
                                </button>
                            </div>
                        </div>
                        <label class="block"
                            ><span
                                class="mb-2 flex items-center gap-2 text-xs font-semibold"
                                ><MapPin class="size-3.5 text-[#b15d3d]" />
                                集合信息</span
                            ><textarea
                                v-model="meetingInfo"
                                rows="3"
                                class="w-full resize-none rounded-xl border border-[#dad7ca] bg-[#faf9f3] p-4 text-sm dark:bg-background"
                                placeholder="可在成团后补充集合时间、地点与联系人"
                            /></label
                        ><label class="block"
                            ><span class="mb-2 block text-xs font-semibold"
                                >补充说明</span
                            ><textarea
                                v-model="notes"
                                rows="3"
                                class="w-full resize-none rounded-xl border border-[#dad7ca] bg-[#faf9f3] p-4 text-sm dark:bg-background"
                                placeholder="面向报名成员的其他说明"
                            />
                        </label>
                        <div>
                            <span
                                class="mb-2 flex items-center gap-2 text-xs font-semibold"
                                ><FileText class="size-3.5 text-[#b15d3d]" />
                                活动方案 PDF</span
                            >
                            <label
                                class="flex cursor-pointer flex-col gap-3 rounded-2xl border border-dashed border-[#d3cec1] bg-[#faf9f3] p-5 transition hover:border-[#6e8c81] sm:flex-row sm:items-center sm:justify-between dark:bg-background"
                            >
                                <div>
                                    <p
                                        class="text-sm font-semibold text-[#36584d]"
                                    >
                                        {{
                                            attachmentName ||
                                            '选择 PDF 活动方案文件'
                                        }}
                                    </p>
                                    <p class="mt-1 text-[10px] text-[#929087]">
                                        仅支持 PDF，文件不超过
                                        10MB；新文件会替换原方案。
                                    </p>
                                </div>
                                <span
                                    class="shrink-0 rounded-full bg-[#e6eee8] px-4 py-2 text-xs font-semibold text-[#396052]"
                                    >{{
                                        attachmentName ? '更换文件' : '上传文件'
                                    }}</span
                                >
                                <input
                                    type="file"
                                    accept="application/pdf,.pdf"
                                    class="sr-only"
                                    @change="selectAttachment"
                                />
                            </label>
                            <a
                                v-if="groupRecord?.attachmentUrl && !attachment"
                                :href="groupRecord.attachmentUrl"
                                class="mt-2 inline-flex items-center gap-1.5 text-[10px] font-semibold text-[#b15d3d]"
                            >
                                <Download class="size-3" /> 下载当前 PDF
                            </a>
                        </div>
                        <div>
                            <span
                                class="mb-2 flex items-center gap-2 text-xs font-semibold"
                                ><QrCode class="size-3.5 text-[#b15d3d]" />
                                微信群二维码</span
                            >
                            <label
                                class="flex cursor-pointer flex-col gap-4 rounded-2xl border border-dashed border-[#d3cec1] bg-[#faf9f3] p-5 transition hover:border-[#6e8c81] sm:flex-row sm:items-center dark:bg-background"
                            >
                                <img
                                    v-if="wechatQrCodePreview"
                                    :src="wechatQrCodePreview"
                                    alt="微信群二维码预览"
                                    class="size-24 shrink-0 rounded-xl border border-[#dedbd0] bg-white object-contain p-1"
                                />
                                <span
                                    v-else
                                    class="grid size-24 shrink-0 place-items-center rounded-xl bg-[#e8eee9] text-[#4b7063]"
                                >
                                    <QrCode class="size-10" />
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span
                                        class="block truncate text-sm font-semibold text-[#36584d]"
                                    >
                                        {{
                                            wechatQrCodeName ||
                                            '选择微信群二维码图片'
                                        }}
                                    </span>
                                    <span
                                        class="mt-1 block text-[10px] leading-5 text-[#929087]"
                                    >
                                        支持 JPG、PNG、WebP，不超过 5MB。仅团长、管理员和已确认参团老师可查看。
                                    </span>
                                    <span
                                        class="mt-3 inline-flex rounded-full bg-[#e6eee8] px-4 py-2 text-xs font-semibold text-[#396052]"
                                    >
                                        {{
                                            wechatQrCodeName
                                                ? '更换二维码'
                                                : '上传二维码'
                                        }}
                                    </span>
                                </span>
                                <input
                                    type="file"
                                    accept="image/jpeg,image/png,image/webp,.jpg,.jpeg,.png,.webp"
                                    class="sr-only"
                                    @change="selectWechatQrCode"
                                />
                            </label>
                        </div>
                    </div>
                    <div
                        class="mt-7 flex justify-end gap-3 border-t border-[#e6e2d7] pt-5"
                    >
                        <Link
                            :href="
                                editMode && groupRecord
                                    ? `/groups/${groupRecord.id}`
                                    : `/routes/${route.id}`
                            "
                            class="h-10 rounded-full border border-[#d4d0c4] px-5 text-xs font-semibold"
                        >
                            取消</Link
                        ><button
                            type="button"
                            class="inline-flex h-10 items-center gap-2 rounded-full bg-[#c46140] px-6 text-xs font-semibold text-white"
                            @click="publish"
                        >
                            {{ editMode ? '保存活动信息' : '发布并开放报名' }}
                            <ArrowRight class="size-3.5" />
                        </button>
                    </div>
                </section>
            </div>
        </div>
    </main>
</template>
