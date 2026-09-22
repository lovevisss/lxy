<script setup lang="ts">
import { computed, reactive, ref } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    AlertTriangle,
    Building2,
    Check,
    ChevronLeft,
    ChevronRight,
    Clock3,
    Search,
    ShieldCheck,
    SlidersHorizontal,
    UserCog,
    UsersRound,
    X,
} from '@lucide/vue';
import { toast } from 'vue-sonner';
import PageIntro from '@/components/retreat/PageIntro.vue';

type Role = 'admin' | 'group_department_reviewer' | 'group_final_reviewer';
type Person = {
    id: number;
    name: string;
    staffNumber?: string | null;
    department?: string | null;
    email: string;
    roles: Role[];
    departmentRoleStale: boolean;
    configuredAt?: string | null;
    configuredBy?: string | null;
};
type Audit = {
    id: number;
    action: 'granted' | 'revoked';
    role: Role;
    department?: string | null;
    target: string;
    actor: string;
    time: string;
};

const props = defineProps<{
    users: {
        data: Person[];
        current_page: number;
        last_page: number;
        prev_page_url?: string | null;
        next_page_url?: string | null;
        total: number;
    };
    departments: string[];
    filters: { search: string; department: string; role: string };
    stats: {
        admins: number;
        departmentReviewers: number;
        finalReviewers: number;
        coveredDepartments: number;
        totalDepartments: number;
    };
    audits: {
        data: Audit[];
        current_page: number;
        last_page: number;
        prev_page_url?: string | null;
        next_page_url?: string | null;
        total: number;
    };
}>();

defineOptions({
    layout: { breadcrumbs: [{ title: '权限配置', href: '/permissions' }] },
});

const page = usePage();
const activeTab = ref<'people' | 'audit'>('people');
const filters = reactive({ ...props.filters });
const editing = ref<Person | null>(null);
const selectedRoles = ref<Role[]>([]);
const saving = ref(false);

const roleOptions: { value: Role; title: string; description: string }[] = [
    {
        value: 'admin',
        title: '系统管理员',
        description: '维护人员权限与系统配置，不自动获得审批权。',
    },
    {
        value: 'group_department_reviewer',
        title: '分院审核人',
        description: '确认本人所属单位的正式团员名单。',
    },
    {
        value: 'group_final_reviewer',
        title: '总审核人',
        description: '所有分院会签完成后执行成团终审。',
    },
];
const roleLabels: Record<Role, string> = {
    admin: '系统管理员',
    group_department_reviewer: '分院审核人',
    group_final_reviewer: '总审核人',
};
const coverage = computed(() =>
    props.stats.totalDepartments
        ? Math.round(
              (props.stats.coveredDepartments / props.stats.totalDepartments) *
                  100,
          )
        : 0,
);

function search() {
    router.get('/permissions', filters, {
        preserveState: true,
        replace: true,
    });
}

function openEditor(person: Person) {
    editing.value = person;
    selectedRoles.value = [...person.roles];
}

function toggleRole(role: Role) {
    selectedRoles.value = selectedRoles.value.includes(role)
        ? selectedRoles.value.filter((item) => item !== role)
        : [...selectedRoles.value, role];
}

function save() {
    if (!editing.value) {
        return;
    }

    router.put(
        `/permissions/users/${editing.value.id}`,
        { roles: selectedRoles.value },
        {
            preserveScroll: true,
            onStart: () => (saving.value = true),
            onFinish: () => (saving.value = false),
            onSuccess: () => {
                editing.value = null;
                toast.success('人员权限已更新');
            },
            onError: (errors) =>
                toast.error('权限更新失败', {
                    description: Object.values(errors)[0],
                }),
        },
    );
}
</script>

<template>
    <Head title="权限配置中心" />
    <main
        class="paper-grid min-h-full bg-[#f8f7f0] p-4 sm:p-6 lg:p-8 dark:bg-background"
    >
        <div class="mx-auto max-w-[1480px]">
            <PageIntro
                eyebrow="Access governance"
                title="让每一道授权，都清晰可追溯"
                description="统一配置系统管理员、分院审核人与总审核人。角色可兼任，审批范围由教师当前所属单位自动确定。"
            />

            <section class="mt-7 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <article
                    v-for="card in [
                        {
                            label: '系统管理员',
                            value: stats.admins,
                            note: '管理配置，不代办审批',
                            icon: ShieldCheck,
                            tone: 'bg-[#e3eee8] text-[#28594a]',
                        },
                        {
                            label: '分院审核人',
                            value: stats.departmentReviewers,
                            note: '按本人单位会签',
                            icon: Building2,
                            tone: 'bg-[#f2ead9] text-[#8a632f]',
                        },
                        {
                            label: '总审核人',
                            value: stats.finalReviewers,
                            note: '负责全校成团终审',
                            icon: UserCog,
                            tone: 'bg-[#f2e3dd] text-[#a25339]',
                        },
                        {
                            label: '单位覆盖率',
                            value: `${coverage}%`,
                            note: `${stats.coveredDepartments} / ${stats.totalDepartments} 个单位`,
                            icon: UsersRound,
                            tone: 'bg-[#e5e9f0] text-[#53647b]',
                        },
                    ]"
                    :key="card.label"
                    class="enter-up flex items-center gap-4 rounded-[22px] border border-[#dfddd2] bg-[#fffefa]/90 p-5 shadow-[0_16px_45px_rgba(45,60,49,.05)] dark:border-border dark:bg-card"
                >
                    <div
                        :class="[
                            'grid size-12 shrink-0 place-items-center rounded-2xl',
                            card.tone,
                        ]"
                    >
                        <component :is="card.icon" class="size-5" />
                    </div>
                    <div>
                        <p class="text-[11px] text-[#85847b]">
                            {{ card.label }}
                        </p>
                        <p
                            class="font-serif-cn mt-1 text-2xl font-semibold text-[#24483c] dark:text-foreground"
                        >
                            {{ card.value }}
                        </p>
                        <p class="mt-1 text-[10px] text-[#aaa79e]">
                            {{ card.note }}
                        </p>
                    </div>
                </article>
            </section>

            <div
                v-if="coverage < 100"
                class="mt-5 flex flex-wrap items-center gap-3 rounded-2xl border border-[#ead6ad] bg-[#fff8e9] px-5 py-4 text-xs text-[#795b2f]"
            >
                <AlertTriangle class="size-4 shrink-0 text-[#b8782f]" />
                尚有 {{ stats.totalDepartments - stats.coveredDepartments }}
                个单位未配置有效审核人；涉及这些单位的团队将无法提交成团审批。
            </div>

            <section
                class="mt-6 overflow-hidden rounded-[26px] border border-[#dfdcd0] bg-[#fffefa] dark:border-border dark:bg-card"
            >
                <div
                    class="flex flex-wrap items-center justify-between gap-4 border-b border-[#e6e2d7] p-4 md:px-6 dark:border-border"
                >
                    <div
                        class="flex gap-1 rounded-full bg-[#efede4] p-1 dark:bg-muted"
                    >
                        <button
                            v-for="tab in [
                                { key: 'people', label: '人员权限' },
                                { key: 'audit', label: '变更记录' },
                            ] as const"
                            :key="tab.key"
                            type="button"
                            :class="[
                                'rounded-full px-5 py-2 text-xs transition',
                                activeTab === tab.key
                                    ? 'bg-white font-semibold text-[#214a3d] shadow-sm dark:bg-card'
                                    : 'text-[#85847b]',
                            ]"
                            @click="activeTab = tab.key"
                        >
                            {{ tab.label }}
                        </button>
                    </div>
                    <span class="text-[10px] text-[#99978e]">
                        共
                        {{
                            activeTab === 'people' ? users.total : audits.total
                        }}
                        条记录
                    </span>
                </div>

                <div v-if="activeTab === 'people'">
                    <form
                        class="grid gap-3 border-b border-[#ebe7dd] p-4 md:grid-cols-[1fr_220px_220px_auto] md:px-6 dark:border-border"
                        @submit.prevent="search"
                    >
                        <label class="relative">
                            <Search
                                class="absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-[#99978e]"
                            />
                            <input
                                v-model="filters.search"
                                class="h-10 w-full rounded-xl border border-[#ddd9cd] bg-[#faf9f4] pr-4 pl-10 text-xs outline-none focus:border-[#55786d] dark:bg-background"
                                placeholder="搜索姓名、工号或单位"
                            />
                        </label>
                        <select
                            v-model="filters.department"
                            class="h-10 rounded-xl border border-[#ddd9cd] bg-[#faf9f4] px-3 text-xs outline-none dark:bg-background"
                        >
                            <option value="">全部单位</option>
                            <option
                                v-for="item in departments"
                                :key="item"
                                :value="item"
                            >
                                {{ item }}
                            </option>
                        </select>
                        <select
                            v-model="filters.role"
                            class="h-10 rounded-xl border border-[#ddd9cd] bg-[#faf9f4] px-3 text-xs outline-none dark:bg-background"
                        >
                            <option value="">全部角色</option>
                            <option
                                v-for="item in roleOptions"
                                :key="item.value"
                                :value="item.value"
                            >
                                {{ item.title }}
                            </option>
                        </select>
                        <button
                            type="submit"
                            class="inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-[#1d4b3e] px-5 text-xs font-semibold text-white"
                        >
                            <SlidersHorizontal class="size-3.5" /> 筛选
                        </button>
                    </form>

                    <div class="hidden overflow-x-auto md:block">
                        <table class="w-full text-left text-xs">
                            <thead
                                class="bg-[#f5f3eb] text-[10px] tracking-[.08em] text-[#8d8b82] uppercase dark:bg-muted"
                            >
                                <tr>
                                    <th class="px-6 py-3.5 font-medium">
                                        人员
                                    </th>
                                    <th class="px-5 py-3.5 font-medium">
                                        所属单位
                                    </th>
                                    <th class="px-5 py-3.5 font-medium">
                                        当前角色
                                    </th>
                                    <th class="px-5 py-3.5 font-medium">
                                        最近配置
                                    </th>
                                    <th
                                        class="px-6 py-3.5 text-right font-medium"
                                    >
                                        操作
                                    </th>
                                </tr>
                            </thead>
                            <tbody
                                class="divide-y divide-[#ebe7dd] dark:divide-border"
                            >
                                <tr
                                    v-for="person in users.data"
                                    :key="person.id"
                                    class="transition hover:bg-[#faf8f1] dark:hover:bg-muted/50"
                                >
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <span
                                                class="font-serif-cn grid size-9 place-items-center rounded-xl bg-[#e4ece7] font-semibold text-[#31594c]"
                                            >
                                                {{ person.name.slice(-1) }}
                                            </span>
                                            <div>
                                                <p
                                                    class="font-semibold text-[#2d4c42] dark:text-foreground"
                                                >
                                                    {{ person.name }}
                                                </p>
                                                <p
                                                    class="mt-1 text-[10px] text-[#9a988f]"
                                                >
                                                    {{
                                                        person.staffNumber ||
                                                        '无工号'
                                                    }}
                                                    · {{ person.email }}
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 text-[#626b65]">
                                        {{ person.department || '未设置单位' }}
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="flex flex-wrap gap-1.5">
                                            <span
                                                v-for="role in person.roles"
                                                :key="role"
                                                class="rounded-full bg-[#edf1eb] px-2.5 py-1 text-[10px] font-semibold text-[#3b6355]"
                                            >
                                                {{ roleLabels[role] }}
                                            </span>
                                            <span
                                                v-if="!person.roles.length"
                                                class="text-[#aaa89f]"
                                                >普通教师</span
                                            >
                                            <span
                                                v-if="
                                                    person.departmentRoleStale
                                                "
                                                class="inline-flex items-center gap-1 rounded-full bg-[#f8e9e2] px-2.5 py-1 text-[10px] font-semibold text-[#a15339]"
                                            >
                                                <AlertTriangle class="size-3" />
                                                单位已变化
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 text-[#7e7d75]">
                                        <p>{{ person.configuredAt || '—' }}</p>
                                        <p
                                            v-if="person.configuredBy"
                                            class="mt-1 text-[10px] text-[#aaa89f]"
                                        >
                                            {{ person.configuredBy }} 操作
                                        </p>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <button
                                            type="button"
                                            class="rounded-full border border-[#d9d5c9] px-4 py-2 font-semibold text-[#42675b] transition hover:border-[#648478]"
                                            @click="openEditor(person)"
                                        >
                                            配置权限
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div
                        class="divide-y divide-[#ebe7dd] md:hidden dark:divide-border"
                    >
                        <article
                            v-for="person in users.data"
                            :key="person.id"
                            class="p-4"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="font-semibold text-[#2d4c42]">
                                        {{ person.name }}
                                    </p>
                                    <p class="mt-1 text-[10px] text-[#96948b]">
                                        {{ person.department || '未设置单位' }}
                                        · {{ person.staffNumber || '无工号' }}
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    class="rounded-full border border-[#d9d5c9] px-3 py-1.5 text-[10px] font-semibold"
                                    @click="openEditor(person)"
                                >
                                    配置
                                </button>
                            </div>
                            <div class="mt-3 flex flex-wrap gap-1.5">
                                <span
                                    v-for="role in person.roles"
                                    :key="role"
                                    class="rounded-full bg-[#edf1eb] px-2.5 py-1 text-[10px] text-[#3b6355]"
                                    >{{ roleLabels[role] }}</span
                                >
                                <span
                                    v-if="!person.roles.length"
                                    class="text-[10px] text-[#aaa89f]"
                                    >普通教师</span
                                >
                            </div>
                        </article>
                    </div>

                    <div
                        v-if="!users.data.length"
                        class="grid min-h-48 place-items-center text-center text-xs text-[#8f8d84]"
                    >
                        没有匹配的人员，请调整筛选条件。
                    </div>

                    <div
                        class="flex items-center justify-between border-t border-[#ebe7dd] px-5 py-4 text-xs dark:border-border"
                    >
                        <span class="text-[#918f86]"
                            >第 {{ users.current_page }} /
                            {{ users.last_page }} 页</span
                        >
                        <div class="flex gap-2">
                            <Link
                                v-if="users.prev_page_url"
                                :href="users.prev_page_url"
                                class="grid size-9 place-items-center rounded-full border border-[#ddd9cd]"
                                ><ChevronLeft class="size-4"
                            /></Link>
                            <span
                                v-else
                                class="grid size-9 place-items-center rounded-full border border-[#e9e6dd] text-[#ccc8bd]"
                                ><ChevronLeft class="size-4"
                            /></span>
                            <Link
                                v-if="users.next_page_url"
                                :href="users.next_page_url"
                                class="grid size-9 place-items-center rounded-full border border-[#ddd9cd]"
                                ><ChevronRight class="size-4"
                            /></Link>
                            <span
                                v-else
                                class="grid size-9 place-items-center rounded-full border border-[#e9e6dd] text-[#ccc8bd]"
                                ><ChevronRight class="size-4"
                            /></span>
                        </div>
                    </div>
                </div>

                <div
                    v-else
                    class="divide-y divide-[#ebe7dd] dark:divide-border"
                >
                    <article
                        v-for="audit in audits.data"
                        :key="audit.id"
                        class="flex gap-4 px-5 py-4 md:px-6"
                    >
                        <span
                            :class="[
                                'mt-0.5 grid size-9 shrink-0 place-items-center rounded-xl',
                                audit.action === 'granted'
                                    ? 'bg-[#e3eee8] text-[#2f6553]'
                                    : 'bg-[#f4e6e0] text-[#a4563b]',
                            ]"
                        >
                            <Check
                                v-if="audit.action === 'granted'"
                                class="size-4"
                            />
                            <X v-else class="size-4" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs text-[#4f5e57]">
                                <strong>{{ audit.actor }}</strong>
                                {{
                                    audit.action === 'granted'
                                        ? '授予'
                                        : '撤销了'
                                }}
                                <strong>{{ audit.target }}</strong> 的“{{
                                    roleLabels[audit.role]
                                }}”权限
                            </p>
                            <p
                                v-if="audit.department"
                                class="mt-1 text-[10px] text-[#918f86]"
                            >
                                授权范围：{{ audit.department }}
                            </p>
                        </div>
                        <span
                            class="inline-flex shrink-0 items-center gap-1 text-[10px] text-[#aaa89f]"
                            ><Clock3 class="size-3" />{{ audit.time }}</span
                        >
                    </article>
                    <div
                        v-if="!audits.data.length"
                        class="grid min-h-56 place-items-center text-center text-sm text-[#8f8d84]"
                    >
                        暂无权限变更记录
                    </div>
                    <div
                        class="flex items-center justify-between px-5 py-4 text-xs md:px-6"
                    >
                        <span class="text-[#918f86]"
                            >第 {{ audits.current_page }} /
                            {{ audits.last_page }} 页</span
                        >
                        <div class="flex gap-2">
                            <Link
                                v-if="audits.prev_page_url"
                                :href="audits.prev_page_url"
                                preserve-state
                                class="grid size-9 place-items-center rounded-full border border-[#ddd9cd]"
                                ><ChevronLeft class="size-4"
                            /></Link>
                            <Link
                                v-if="audits.next_page_url"
                                :href="audits.next_page_url"
                                preserve-state
                                class="grid size-9 place-items-center rounded-full border border-[#ddd9cd]"
                                ><ChevronRight class="size-4"
                            /></Link>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <Teleport to="body">
            <Transition name="fade">
                <div
                    v-if="editing"
                    class="fixed inset-0 z-50 bg-[#102d26]/35 backdrop-blur-[2px]"
                    @click.self="editing = null"
                >
                    <Transition name="slide" appear>
                        <aside
                            class="absolute top-0 right-0 flex h-full w-full max-w-md flex-col bg-[#fffefa] shadow-[-28px_0_80px_rgba(25,55,46,.18)] dark:bg-card"
                        >
                            <div
                                class="flex items-start justify-between border-b border-[#e6e2d7] p-6 dark:border-border"
                            >
                                <div>
                                    <p
                                        class="text-[10px] font-semibold tracking-[.16em] text-[#aa5b3d] uppercase"
                                    >
                                        Role assignment
                                    </p>
                                    <h2
                                        class="font-serif-cn mt-2 text-2xl font-semibold text-[#24483c] dark:text-foreground"
                                    >
                                        配置人员权限
                                    </h2>
                                </div>
                                <button
                                    type="button"
                                    class="grid size-9 place-items-center rounded-full border border-[#ddd9cd]"
                                    @click="editing = null"
                                >
                                    <X class="size-4" />
                                </button>
                            </div>
                            <div class="flex-1 overflow-y-auto p-6">
                                <div
                                    class="rounded-2xl bg-[#f2f0e7] p-4 dark:bg-muted"
                                >
                                    <p
                                        class="font-serif-cn text-lg font-semibold"
                                    >
                                        {{ editing.name }}
                                    </p>
                                    <p class="mt-1 text-xs text-[#7f7e75]">
                                        {{ editing.department || '未设置单位' }}
                                        · {{ editing.staffNumber || '无工号' }}
                                    </p>
                                </div>
                                <div class="mt-6 space-y-3">
                                    <button
                                        v-for="option in roleOptions"
                                        :key="option.value"
                                        type="button"
                                        :disabled="
                                            (option.value ===
                                                'group_department_reviewer' &&
                                                !editing.department) ||
                                            (option.value === 'admin' &&
                                                editing.id ===
                                                    page.props.auth.user.id &&
                                                selectedRoles.includes('admin'))
                                        "
                                        :class="[
                                            'flex w-full items-start gap-4 rounded-2xl border p-4 text-left transition',
                                            selectedRoles.includes(option.value)
                                                ? 'border-[#52796c] bg-[#edf3ef]'
                                                : 'border-[#dfdcd0] hover:bg-[#faf8f1]',
                                            option.value ===
                                                'group_department_reviewer' &&
                                            !editing.department
                                                ? 'cursor-not-allowed opacity-45'
                                                : '',
                                        ]"
                                        @click="toggleRole(option.value)"
                                    >
                                        <span
                                            :class="[
                                                'mt-0.5 grid size-5 shrink-0 place-items-center rounded-md border',
                                                selectedRoles.includes(
                                                    option.value,
                                                )
                                                    ? 'border-[#315f50] bg-[#315f50] text-white'
                                                    : 'border-[#c9c5b9] bg-white',
                                            ]"
                                            ><Check
                                                v-if="
                                                    selectedRoles.includes(
                                                        option.value,
                                                    )
                                                "
                                                class="size-3.5"
                                        /></span>
                                        <span>
                                            <span
                                                class="block text-sm font-semibold text-[#345247] dark:text-foreground"
                                                >{{ option.title }}</span
                                            >
                                            <span
                                                class="mt-1 block text-xs leading-5 text-[#85847b]"
                                                >{{ option.description }}</span
                                            >
                                            <span
                                                v-if="
                                                    option.value ===
                                                        'group_department_reviewer' &&
                                                    editing.department
                                                "
                                                class="mt-2 block text-[10px] font-semibold text-[#a25c40]"
                                                >审核范围：{{
                                                    editing.department
                                                }}</span
                                            >
                                        </span>
                                    </button>
                                </div>
                                <div
                                    v-if="
                                        editing.id ===
                                            page.props.auth.user.id &&
                                        selectedRoles.includes('admin')
                                    "
                                    class="mt-5 rounded-2xl border border-[#ead9bd] bg-[#fff8ea] p-4 text-xs leading-5 text-[#7b633d]"
                                >
                                    为避免管理员误锁定自己，当前账号的管理员权限不能在此移除。
                                </div>
                            </div>
                            <div
                                class="flex justify-end gap-3 border-t border-[#e6e2d7] p-5 dark:border-border"
                            >
                                <button
                                    type="button"
                                    class="h-10 rounded-full border border-[#d7d3c7] px-5 text-xs font-semibold"
                                    @click="editing = null"
                                >
                                    取消
                                </button>
                                <button
                                    type="button"
                                    :disabled="saving"
                                    class="h-10 rounded-full bg-[#1d4b3e] px-6 text-xs font-semibold text-white disabled:opacity-50"
                                    @click="save"
                                >
                                    {{ saving ? '保存中…' : '保存权限' }}
                                </button>
                            </div>
                        </aside>
                    </Transition>
                </div>
            </Transition>
        </Teleport>
    </main>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
.slide-enter-active,
.slide-leave-active {
    transition: transform 0.32s cubic-bezier(0.2, 0.75, 0.25, 1);
}
.slide-enter-from,
.slide-leave-to {
    transform: translateX(100%);
}
</style>
