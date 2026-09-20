<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import {
    BadgeCheck,
    Plus,
    Smartphone,
    Trash2,
    UserPlus,
    UserRound,
    UsersRound,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';

type FamilyMember = {
    name: string;
    relationship: string;
};

type AvailableMember = {
    id: number;
    name: string;
    department: string;
    email: string;
};

const props = withDefaults(
    defineProps<{
        groupId: number;
        mode: 'member' | 'leader-family';
        availableMembers?: AvailableMember[];
        familyMembers?: FamilyMember[];
        remainingCapacity?: number;
    }>(),
    {
        availableMembers: () => [],
        familyMembers: () => [],
        remainingCapacity: 0,
    },
);

const emit = defineEmits<{ saved: [] }>();
const open = ref(false);
const processing = ref(false);
const selectedUserId = ref<number | ''>('');
const contactMobile = ref('');
const family = ref<FamilyMember[]>([]);
const errors = ref<Record<string, string>>({});

const isMemberMode = computed(() => props.mode === 'member');
const title = computed(() =>
    isMemberMode.value ? '添加团员' : '维护我的随行家属',
);
const totalPeople = computed(() => family.value.length + 1);
const familyLimit = computed(() => {
    if (isMemberMode.value) {
        return Math.max(0, Math.min(9, props.remainingCapacity - 1));
    }

    return Math.max(
        0,
        Math.min(9, props.familyMembers.length + props.remainingCapacity),
    );
});
const canAddFamily = computed(() => family.value.length < familyLimit.value);

watch(open, (isOpen) => {
    if (!isOpen) {
        return;
    }

    errors.value = {};
    selectedUserId.value = '';
    contactMobile.value = '';
    family.value = isMemberMode.value
        ? []
        : props.familyMembers.map((member) => ({ ...member }));
});

function addFamilyMember() {
    if (canAddFamily.value) {
        family.value.push({ name: '', relationship: '配偶' });
    }
}

function removeFamilyMember(index: number) {
    family.value.splice(index, 1);
}

function submit() {
    errors.value = {};
    const options = {
        preserveScroll: true,
        onStart: () => (processing.value = true),
        onFinish: () => (processing.value = false),
        onError: (validationErrors: Record<string, string>) => {
            errors.value = validationErrors;
            toast.error('请检查人员信息', {
                description:
                    Object.values(validationErrors)[0] ?? '提交内容不完整。',
            });
        },
        onSuccess: () => {
            open.value = false;
            toast.success(
                isMemberMode.value ? '团员已直接加入' : '随行家属已更新',
                {
                    description: isMemberMode.value
                        ? '该团员可登录系统查看行程，并在成团后完成最终确认。'
                        : `当前按团长本人及 ${family.value.length} 名家属计入名额。`,
                },
            );
            emit('saved');
        },
    };

    if (isMemberMode.value) {
        router.post(
            `/groups/${props.groupId}/members`,
            {
                user_id: selectedUserId.value,
                contact_mobile: contactMobile.value,
                family_members: family.value,
            },
            options,
        );
    } else {
        router.put(
            `/groups/${props.groupId}/leader-family`,
            { family_members: family.value },
            options,
        );
    }
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogTrigger as-child>
            <slot />
        </DialogTrigger>
        <DialogContent
            class="max-h-[88vh] overflow-y-auto border-[#d9d4c7] bg-[#fffefa] p-0 sm:max-w-2xl"
        >
            <div
                class="relative overflow-hidden border-b border-[#e5e0d4] bg-[#f3f1e8] px-6 py-6 sm:px-8"
            >
                <div
                    class="absolute -top-10 -right-8 size-32 rounded-full border border-[#d7cdbb]"
                />
                <DialogHeader class="relative">
                    <p
                        class="text-[10px] font-semibold tracking-[.2em] text-[#b25c3d] uppercase"
                    >
                        Leader member desk
                    </p>
                    <DialogTitle
                        class="font-serif-cn mt-2 text-2xl text-[#23483c]"
                    >
                        {{ title }}
                    </DialogTitle>
                    <DialogDescription class="mt-1 max-w-lg text-xs leading-6">
                        {{
                            isMemberMode
                                ? '从已注册教职工中选择成员，保存后直接获得正式参团资格。'
                                : '家属无需单独账号，将与团长本人一起计入已确认人数。'
                        }}
                    </DialogDescription>
                </DialogHeader>
            </div>

            <form class="px-6 pb-7 sm:px-8" @submit.prevent="submit">
                <div
                    class="mt-6 flex items-center justify-between rounded-2xl bg-[#173f35] p-4 text-white"
                >
                    <div class="flex items-center gap-3">
                        <span
                            class="grid size-10 place-items-center rounded-full bg-white/10"
                        >
                            <UsersRound class="size-4 text-[#edbf79]" />
                        </span>
                        <div>
                            <p class="text-[10px] text-white/55">
                                当前可用名额
                            </p>
                            <p class="mt-0.5 text-sm font-semibold">
                                还可安排 {{ remainingCapacity }} 人
                            </p>
                        </div>
                    </div>
                    <span class="font-serif-cn text-2xl text-[#edbf79]">
                        {{ totalPeople }}
                    </span>
                </div>

                <template v-if="isMemberMode">
                    <label class="mt-6 block">
                        <span
                            class="mb-2 flex items-center gap-2 text-xs font-semibold text-[#315348]"
                        >
                            <UserPlus class="size-3.5 text-[#b65f40]" />
                            选择已注册教职工 *
                        </span>
                        <select
                            v-model="selectedUserId"
                            required
                            class="h-11 w-full rounded-xl border border-[#d9d4c7] bg-[#faf9f3] px-4 text-sm outline-none focus:border-[#4e7568]"
                        >
                            <option value="" disabled>请选择人员</option>
                            <option
                                v-for="member in availableMembers"
                                :key="member.id"
                                :value="member.id"
                            >
                                {{ member.name }} · {{ member.department }} ·
                                {{ member.email }}
                            </option>
                        </select>
                        <p
                            v-if="!availableMembers.length"
                            class="mt-2 text-[10px] text-[#a35d45]"
                        >
                            暂无可添加账号，已在团内的人员不会重复显示。
                        </p>
                    </label>

                    <label class="mt-4 block">
                        <span
                            class="mb-2 flex items-center gap-2 text-xs font-semibold text-[#315348]"
                        >
                            <Smartphone class="size-3.5 text-[#b65f40]" />
                            短信联系手机号 *
                        </span>
                        <input
                            v-model="contactMobile"
                            type="tel"
                            required
                            maxlength="11"
                            pattern="1[3-9][0-9]{9}"
                            class="h-11 w-full rounded-xl border border-[#d9d4c7] bg-[#faf9f3] px-4 text-sm outline-none focus:border-[#4e7568]"
                            placeholder="用于成团与最终确认提醒"
                        />
                    </label>
                </template>

                <div
                    v-else
                    class="mt-6 flex items-center gap-3 rounded-2xl border border-[#d9e3dd] bg-[#edf3ee] p-4"
                >
                    <span
                        class="grid size-10 place-items-center rounded-full bg-[#1d4b3e] text-white"
                    >
                        <UserRound class="size-4" />
                    </span>
                    <div>
                        <p class="text-[10px] text-[#77837b]">固定包含</p>
                        <p class="mt-0.5 text-sm font-semibold text-[#294c41]">
                            团长本人 · 已计入 1 人
                        </p>
                    </div>
                </div>

                <div class="mt-6 flex items-end justify-between gap-4">
                    <div>
                        <h3 class="text-sm font-semibold text-[#315348]">
                            {{
                                isMemberMode
                                    ? '该团员的随行家属'
                                    : '我的随行家属'
                            }}
                        </h3>
                        <p class="mt-1 text-[10px] text-[#8c8b82]">
                            每位家属都将占用一个团队名额
                        </p>
                    </div>
                    <button
                        type="button"
                        :disabled="!canAddFamily"
                        class="inline-flex h-9 shrink-0 items-center gap-1.5 rounded-full border border-[#cfcabd] px-4 text-xs font-semibold text-[#42685b] transition hover:bg-[#edf3ee] disabled:cursor-not-allowed disabled:opacity-40"
                        @click="addFamilyMember"
                    >
                        <Plus class="size-3.5" />添加家属
                    </button>
                </div>

                <div v-if="family.length" class="mt-4 space-y-3">
                    <div
                        v-for="(member, index) in family"
                        :key="index"
                        class="grid gap-3 rounded-2xl border border-[#e2ded2] bg-[#faf8f1] p-4 sm:grid-cols-[1fr_150px_auto] sm:items-end"
                    >
                        <label>
                            <span class="mb-2 block text-[10px] text-[#777b73]"
                                >家属姓名 *</span
                            >
                            <input
                                v-model="member.name"
                                required
                                maxlength="50"
                                class="h-10 w-full rounded-xl border border-[#d9d4c7] bg-white px-3 text-sm outline-none focus:border-[#4e7568]"
                                placeholder="请输入真实姓名"
                            />
                        </label>
                        <label>
                            <span class="mb-2 block text-[10px] text-[#777b73]"
                                >与本人关系 *</span
                            >
                            <select
                                v-model="member.relationship"
                                class="h-10 w-full rounded-xl border border-[#d9d4c7] bg-white px-3 text-sm outline-none focus:border-[#4e7568]"
                            >
                                <option>配偶</option>
                                <option>子女</option>
                                <option>父母</option>
                                <option>其他</option>
                            </select>
                        </label>
                        <button
                            type="button"
                            class="grid size-10 place-items-center rounded-full text-[#a35d45] transition hover:bg-[#f3e6e0]"
                            aria-label="移除家属"
                            @click="removeFamilyMember(index)"
                        >
                            <Trash2 class="size-4" />
                        </button>
                    </div>
                </div>
                <div
                    v-else
                    class="mt-4 rounded-2xl border border-dashed border-[#d8d3c6] p-6 text-center text-xs text-[#949188]"
                >
                    暂未添加随行家属
                </div>

                <div
                    class="mt-6 flex flex-col gap-3 border-t border-[#e5e0d4] pt-5 sm:flex-row sm:items-center sm:justify-between"
                >
                    <span
                        class="inline-flex items-center gap-2 text-[10px] leading-5 text-[#777d76]"
                    >
                        <BadgeCheck class="size-4 text-[#39705d]" />
                        {{
                            isMemberMode
                                ? '保存后无需再次审核'
                                : '保存后立即更新团队已确认人数'
                        }}
                    </span>
                    <button
                        type="submit"
                        :disabled="
                            processing ||
                            (isMemberMode &&
                                (!selectedUserId || !availableMembers.length))
                        "
                        class="h-11 rounded-full bg-[#c46140] px-7 text-sm font-semibold text-white transition hover:bg-[#b45637] disabled:cursor-not-allowed disabled:opacity-45"
                    >
                        {{ processing ? '正在保存…' : '确认保存' }}
                    </button>
                </div>
            </form>
        </DialogContent>
    </Dialog>
</template>
