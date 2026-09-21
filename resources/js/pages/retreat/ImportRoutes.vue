<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowLeft,
    BadgeCheck,
    CircleAlert,
    Download,
    FileText,
    FileSpreadsheet,
    Layers3,
    ShieldCheck,
    UploadCloud,
} from '@lucide/vue';
import { toast } from 'vue-sonner';

type ImportError = {
    routeCode: string;
    message: string;
};

type ImportRecord = {
    id: number;
    filename: string;
    uploader: string;
    status: 'success' | 'partial' | 'failed' | 'processing';
    totalRoutes: number;
    importedRoutes: number;
    failedRoutes: number;
    errors: ImportError[];
    createdAt: string;
};

defineProps<{ importHistory?: ImportRecord[] }>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: '线路库', href: '/routes' },
            { title: '工会批量导入', href: '/routes/import' },
        ],
    },
});

const file = ref<File | null>(null);
const processing = ref(false);
const pdfFile = ref<File | null>(null);
const pdfProcessing = ref(false);

function selectPdf(event: Event) {
    const input = event.target as HTMLInputElement;
    const selected = input.files?.[0] ?? null;

    if (selected && !selected.name.toLowerCase().endsWith('.pdf')) {
        toast.error('请选择 PDF 文件');
        input.value = '';

        return;
    }

    pdfFile.value = selected;
}

function parsePdf() {
    if (!pdfFile.value) {
        toast.warning('请先选择需要解析的 PDF 方案');

        return;
    }

    router.post(
        '/routes/import',
        { pdf: pdfFile.value },
        {
            forceFormData: true,
            onStart: () => (pdfProcessing.value = true),
            onFinish: () => (pdfProcessing.value = false),
            onError: (errors) =>
                toast.error('PDF 解析失败', {
                    description:
                        Object.values(errors)[0] ??
                        '请确认 PDF 含有可提取文字和 D1、D2 等日程标记。',
                }),
        },
    );
}

function selectFile(event: Event) {
    const selected = (event.target as HTMLInputElement).files?.[0] ?? null;

    if (selected && !selected.name.toLowerCase().endsWith('.csv')) {
        toast.error('请选择 CSV 文件');
        (event.target as HTMLInputElement).value = '';

        return;
    }

    file.value = selected;
}

function submitImport() {
    if (!file.value) {
        toast.warning('请先选择需要导入的 CSV 文件');

        return;
    }

    router.post(
        '/routes/import',
        { file: file.value },
        {
            forceFormData: true,
            preserveScroll: true,
            onStart: () => (processing.value = true),
            onFinish: () => (processing.value = false),
            onSuccess: () => {
                file.value = null;
                toast.success('线路导入处理完成', {
                    description: '有效线路已直接发布到已审批线路库。',
                });
            },
            onError: (errors) =>
                toast.error('导入失败', {
                    description:
                        Object.values(errors)[0] ?? '请检查文件格式与模板列。',
                }),
        },
    );
}

function statusLabel(status: ImportRecord['status']) {
    return {
        success: '全部成功',
        partial: '部分成功',
        failed: '导入失败',
        processing: '处理中',
    }[status];
}
</script>

<template>
    <Head title="工会批量导入线路" />
    <main
        class="paper-grid min-h-full bg-[#f8f7f0] p-4 sm:p-6 lg:p-8 dark:bg-background"
    >
        <div class="mx-auto max-w-6xl">
            <Link
                href="/routes"
                class="inline-flex items-center gap-2 text-xs text-[#687169] hover:text-[#b25c3d]"
            >
                <ArrowLeft class="size-3.5" /> 返回线路库
            </Link>

            <section
                class="contour-lines mt-5 overflow-hidden rounded-[30px] bg-[#173f35] text-white shadow-[0_24px_70px_rgba(28,64,53,.16)]"
            >
                <div class="grid lg:grid-cols-[1.05fr_.95fr]">
                    <div class="p-6 md:p-10">
                        <p
                            class="text-[10px] font-semibold tracking-[.2em] text-[#edbf79] uppercase"
                        >
                            Union route import
                        </p>
                        <h1
                            class="font-serif-cn mt-3 text-3xl font-semibold md:text-4xl"
                        >
                            工会统一导入线路
                        </h1>
                        <p
                            class="mt-4 max-w-xl text-sm leading-7 text-white/60"
                        >
                            使用标准模板一次导入多条线路及逐日行程。导入成功的线路视为工会统一发布版本，直接进入已审批线路库。
                        </p>
                        <div class="mt-7 flex flex-wrap gap-3">
                            <a
                                href="/routes/import/template"
                                class="inline-flex h-11 items-center gap-2 rounded-full bg-[#e8bb76] px-5 text-xs font-semibold text-[#173f35]"
                            >
                                <Download class="size-4" /> 下载标准模板
                            </a>
                            <span
                                class="inline-flex h-11 items-center gap-2 rounded-full border border-white/15 px-5 text-xs text-white/70"
                            >
                                <ShieldCheck class="size-4 text-[#edbf79]" />
                                仅校工会账号可操作
                            </span>
                        </div>
                    </div>
                    <div class="flex items-center bg-white/[.06] p-6 md:p-10">
                        <div class="grid w-full grid-cols-3 gap-3 text-center">
                            <div class="rounded-2xl bg-white/[.07] p-4">
                                <Layers3
                                    class="mx-auto size-5 text-[#edbf79]"
                                />
                                <p class="font-serif-cn mt-3 text-2xl">批量</p>
                                <p class="mt-1 text-[10px] text-white/45">
                                    多线路一次导入
                                </p>
                            </div>
                            <div class="rounded-2xl bg-white/[.07] p-4">
                                <FileSpreadsheet
                                    class="mx-auto size-5 text-[#edbf79]"
                                />
                                <p class="font-serif-cn mt-3 text-2xl">CSV</p>
                                <p class="mt-1 text-[10px] text-white/45">
                                    Excel 可直接编辑
                                </p>
                            </div>
                            <div class="rounded-2xl bg-white/[.07] p-4">
                                <BadgeCheck
                                    class="mx-auto size-5 text-[#edbf79]"
                                />
                                <p class="font-serif-cn mt-3 text-2xl">直发</p>
                                <p class="mt-1 text-[10px] text-white/45">
                                    免重复审批
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <div class="mt-6 grid gap-6 lg:grid-cols-[1.08fr_.92fr]">
                <div class="space-y-6">
                    <section
                        class="rounded-[26px] border border-[#b9d1c6] bg-[#eef5f1] p-5 md:p-8 dark:border-border dark:bg-card"
                    >
                        <div class="flex items-start gap-4">
                            <span
                                class="grid size-12 shrink-0 place-items-center rounded-2xl bg-[#1d4b3e] text-white shadow-[0_10px_24px_rgba(29,75,62,.16)]"
                            >
                                <FileText class="size-5" />
                            </span>
                            <div>
                                <p
                                    class="text-[10px] font-semibold tracking-[.18em] text-[#b25c3d] uppercase"
                                >
                                    PDF smart parsing
                                </p>
                                <h2
                                    class="font-serif-cn mt-1 text-2xl font-semibold text-[#244a3d]"
                                >
                                    上传现有方案，生成可编辑草稿
                                </h2>
                                <p
                                    class="mt-2 text-xs leading-6 text-[#758078]"
                                >
                                    自动识别线路名称、交通、服务标准、注意事项和逐日行程；解析后先核对，再提交审批。
                                </p>
                            </div>
                        </div>
                        <label
                            class="mt-6 flex cursor-pointer items-center justify-between gap-4 rounded-2xl border border-dashed border-[#aebfb6] bg-white/70 p-4 transition hover:border-[#487463] dark:bg-muted"
                        >
                            <div class="min-w-0">
                                <p
                                    class="truncate text-sm font-semibold text-[#315348]"
                                >
                                    {{ pdfFile?.name ?? '选择 PDF 行程方案' }}
                                </p>
                                <p class="mt-1 text-[10px] text-[#899089]">
                                    支持文字型 PDF，最大 20MB
                                </p>
                            </div>
                            <span
                                class="shrink-0 rounded-full border border-[#cbd5cf] bg-white px-4 py-2 text-xs font-semibold text-[#49675d] dark:bg-card"
                                >选择文件</span
                            >
                            <input
                                type="file"
                                accept=".pdf,application/pdf"
                                class="sr-only"
                                @change="selectPdf"
                            />
                        </label>
                        <button
                            type="button"
                            :disabled="!pdfFile || pdfProcessing"
                            class="mt-4 inline-flex h-12 w-full items-center justify-center gap-2 rounded-full bg-[#1d4b3e] text-sm font-semibold text-white transition hover:-translate-y-0.5 disabled:cursor-not-allowed disabled:opacity-40"
                            @click="parsePdf"
                        >
                            <FileText class="size-4" />
                            {{
                                pdfProcessing
                                    ? '正在读取与解析…'
                                    : '解析 PDF 并进入草稿'
                            }}
                        </button>
                    </section>

                    <section
                        class="rounded-[26px] border border-[#dfdcd0] bg-[#fffefa] p-5 md:p-8 dark:border-border dark:bg-card"
                    >
                        <div>
                            <p
                                class="text-[10px] font-semibold tracking-[.18em] text-[#b25c3d] uppercase"
                            >
                                Upload file
                            </p>
                            <h2
                                class="font-serif-cn mt-2 text-2xl font-semibold text-[#244a3d]"
                            >
                                上传填写完成的模板
                            </h2>
                        </div>

                        <label
                            class="mt-6 flex min-h-64 cursor-pointer flex-col items-center justify-center rounded-[22px] border border-dashed border-[#c9c2b1] bg-[#f7f5ed] p-8 text-center transition hover:border-[#648174] hover:bg-[#f1f3eb] dark:bg-muted"
                        >
                            <span
                                class="grid size-14 place-items-center rounded-full bg-[#1d4b3e] text-white shadow-[0_10px_24px_rgba(29,75,62,.2)]"
                            >
                                <UploadCloud class="size-6" />
                            </span>
                            <p
                                class="font-serif-cn mt-5 text-lg font-semibold text-[#315348]"
                            >
                                {{ file?.name ?? '选择线路导入文件' }}
                            </p>
                            <p class="mt-2 text-xs leading-6 text-[#8b8a82]">
                                仅支持 CSV，文件不超过 5MB。请使用 UTF-8 CSV
                                格式保存。
                            </p>
                            <span
                                class="mt-4 rounded-full border border-[#d2cec1] bg-white px-4 py-2 text-xs font-semibold text-[#526d63]"
                            >
                                浏览文件
                            </span>
                            <input
                                type="file"
                                accept=".csv,text/csv"
                                class="sr-only"
                                @change="selectFile"
                            />
                        </label>
                        <button
                            type="button"
                            :disabled="!file || processing"
                            class="mt-5 inline-flex h-12 w-full items-center justify-center gap-2 rounded-full bg-[#c46140] text-sm font-semibold text-white transition hover:-translate-y-0.5 disabled:cursor-not-allowed disabled:opacity-40"
                            @click="submitImport"
                        >
                            <UploadCloud class="size-4" />
                            {{
                                processing ? '正在校验并导入…' : '开始导入线路'
                            }}
                        </button>
                    </section>
                </div>

                <aside class="space-y-4">
                    <div
                        class="rounded-[24px] border border-[#dfdcd0] bg-[#fffefa] p-5 dark:border-border dark:bg-card"
                    >
                        <h3 class="text-sm font-semibold text-[#315348]">
                            填写规则
                        </h3>
                        <ol class="mt-4 space-y-4">
                            <li
                                class="flex gap-3 text-xs leading-6 text-[#737870]"
                            >
                                <span
                                    class="font-serif-cn grid size-7 shrink-0 place-items-center rounded-full bg-[#e7eee8] font-semibold text-[#376253]"
                                    >1</span
                                >
                                同一线路的所有日程行使用相同“线路编码”。
                            </li>
                            <li
                                class="flex gap-3 text-xs leading-6 text-[#737870]"
                            >
                                <span
                                    class="font-serif-cn grid size-7 shrink-0 place-items-center rounded-full bg-[#e7eee8] font-semibold text-[#376253]"
                                    >2</span
                                >
                                线路基础资料只需填写在该编码第一行，后续行填写逐日行程。
                            </li>
                            <li
                                class="flex gap-3 text-xs leading-6 text-[#737870]"
                            >
                                <span
                                    class="font-serif-cn grid size-7 shrink-0 place-items-center rounded-full bg-[#e7eee8] font-semibold text-[#376253]"
                                    >3</span
                                >
                                亮点、自理项目等多项内容使用竖线“|”分隔。
                            </li>
                        </ol>
                    </div>
                    <div
                        class="rounded-[24px] border border-[#e2c78f] bg-[#fff8e9] p-5"
                    >
                        <div class="flex items-start gap-3">
                            <CircleAlert
                                class="mt-0.5 size-5 shrink-0 text-[#a65c39]"
                            />
                            <div>
                                <h3
                                    class="text-sm font-semibold text-[#75452f]"
                                >
                                    导入校验
                                </h3>
                                <p
                                    class="mt-2 text-xs leading-6 text-[#8d7060]"
                                >
                                    系统会检查必填项目、人数范围、日程天数连续性和重复线路名称。部分线路失败不会影响同批次其他有效线路。
                                </p>
                            </div>
                        </div>
                    </div>
                </aside>
            </div>

            <section
                class="mt-6 rounded-[26px] border border-[#dfdcd0] bg-[#fffefa] p-5 md:p-8 dark:border-border dark:bg-card"
            >
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <p
                            class="text-[10px] font-semibold tracking-[.18em] text-[#b25c3d] uppercase"
                        >
                            Import history
                        </p>
                        <h2
                            class="font-serif-cn mt-2 text-2xl font-semibold text-[#244a3d]"
                        >
                            最近导入批次
                        </h2>
                    </div>
                    <span class="text-xs text-[#929087]">
                        保留文件、操作人和错误明细
                    </span>
                </div>

                <div v-if="importHistory?.length" class="mt-6 space-y-3">
                    <article
                        v-for="record in importHistory"
                        :key="record.id"
                        class="rounded-2xl border border-[#e2ded2] p-4 md:p-5"
                    >
                        <div
                            class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <div class="flex min-w-0 items-center gap-3">
                                <span
                                    class="grid size-10 shrink-0 place-items-center rounded-xl bg-[#edf1e9] text-[#3c6657]"
                                >
                                    <FileSpreadsheet class="size-4" />
                                </span>
                                <div class="min-w-0">
                                    <p
                                        class="truncate text-sm font-semibold text-[#315348]"
                                    >
                                        {{ record.filename }}
                                    </p>
                                    <p class="mt-1 text-[10px] text-[#959289]">
                                        {{ record.uploader }} ·
                                        {{ record.createdAt }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-[10px] text-[#777b73]">
                                    共 {{ record.totalRoutes }} 条 · 成功
                                    {{ record.importedRoutes }} · 失败
                                    {{ record.failedRoutes }}
                                </span>
                                <span
                                    :class="[
                                        'rounded-full px-3 py-1 text-[10px] font-semibold',
                                        record.status === 'success'
                                            ? 'bg-[#e6eee8] text-[#396052]'
                                            : record.status === 'partial'
                                              ? 'bg-[#fff0d7] text-[#91612f]'
                                              : 'bg-[#f2e8e4] text-[#94533f]',
                                    ]"
                                >
                                    {{ statusLabel(record.status) }}
                                </span>
                            </div>
                        </div>
                        <div
                            v-if="record.errors.length"
                            class="mt-4 rounded-xl bg-[#fbf1ed] p-3"
                        >
                            <p
                                v-for="error in record.errors"
                                :key="`${record.id}-${error.routeCode}-${error.message}`"
                                class="text-[10px] leading-5 text-[#8e5946]"
                            >
                                {{ error.routeCode }}：{{ error.message }}
                            </p>
                        </div>
                    </article>
                </div>
                <div
                    v-else
                    class="mt-6 rounded-2xl border border-dashed border-[#d7d3c7] p-10 text-center text-xs text-[#918f86]"
                >
                    暂无线路导入记录
                </div>
            </section>
        </div>
    </main>
</template>
