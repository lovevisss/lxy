<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import {
    CircleDollarSign,
    Plane,
    Plus,
    Smartphone,
    Trash2,
    UserRound,
    UsersRound,
} from '@lucide/vue';
import { computed, ref } from 'vue';
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

const props = withDefaults(
    defineProps<{
        groupId: number;
        groupTitle: string;
        maxParticipants?: number;
        dailySubsidy?: number;
        estimatedSubsidy?: number;
        selfFundedItems?: string[];
        approvalMode?: 'automatic' | 'manual';
    }>(),
    {
        maxParticipants: 10,
        dailySubsidy: 500,
        estimatedSubsidy: 0,
        selfFundedItems: () => [],
        approvalMode: 'manual',
    },
);

const emit = defineEmits<{
    submitted: [status: 'pending' | 'approved'];
}>();
const page = usePage();
const open = ref(false);
const processing = ref(false);
const message = ref('');
const contactMobile = ref('');
const familyMembers = ref<FamilyMember[]>([]);
const errors = ref<Record<string, string>>({});
const totalPeople = computed(() => familyMembers.value.length + 1);
const canAddFamily = computed(
    () => totalPeople.value < Math.min(props.maxParticipants, 10),
);

function addFamilyMember() {
    if (!canAddFamily.value) {
        return;
    }

    familyMembers.value.push({ name: '', relationship: '配偶' });
}

function removeFamilyMember(index: number) {
    familyMembers.value.splice(index, 1);
}

function submit() {
    errors.value = {};
    router.post(
        `/groups/${props.groupId}/applications`,
        {
            member_count: totalPeople.value,
            family_members: familyMembers.value,
            contact_mobile: contactMobile.value,
            message: message.value,
        },
        {
            preserveScroll: true,
            onStart: () => (processing.value = true),
            onFinish: () => (processing.value = false),
            onError: (validationErrors) => {
                errors.value = validationErrors;
                toast.error('请检查参团信息', {
                    description:
                        Object.values(validationErrors)[0] ??
                        '家属姓名和关系需要填写完整。',
                });
            },
            onSuccess: () => {
                const submittedCount = totalPeople.value;
                open.value = false;
                familyMembers.value = [];
                contactMobile.value = '';
                message.value = '';
                toast.success(
                    props.approvalMode === 'automatic'
                        ? '报名成功，已自动参团'
                        : '参团申请已提交',
                    {
                        description:
                            props.approvalMode === 'automatic'
                                ? `共 ${submittedCount} 人，已自动确认参团资格。`
                                : `共 ${submittedCount} 人，等待团长审核。`,
                    },
                );
                emit(
                    'submitted',
                    props.approvalMode === 'automatic' ? 'approved' : 'pending',
                );
            },
        },
    );
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogTrigger as-child>
            <slot />
        </DialogTrigger>
        <DialogContent
            class="max-h-[88vh] overflow-y-auto border-[#ddd8ca] bg-[#fffefa] p-0 sm:max-w-2xl"
        >
            <div class="border-b border-[#e5e0d4] px-6 py-5 sm:px-8">
                <DialogHeader>
                    <p
                        class="text-[10px] font-semibold tracking-[.18em] text-[#ad5b3e] uppercase"
                    >
                        Join this journey
                    </p>
                    <DialogTitle
                        class="font-serif-cn mt-2 text-2xl text-[#23483c]"
                    >
                        填写参团人员
                    </DialogTitle>
                    <DialogDescription class="mt-1 text-xs leading-6">
                        {{
                            groupTitle
                        }}。申请中默认包含本人，携带家属时请逐一补充姓名和关系。
                    </DialogDescription>
                </DialogHeader>
            </div>

            <form class="px-6 pb-6 sm:px-8 sm:pb-8" @submit.prevent="submit">
                <div
                    :class="[
                        'mt-6 flex items-start gap-3 rounded-2xl border p-4',
                        approvalMode === 'automatic'
                            ? 'border-[#cfe0d5] bg-[#edf3ee]'
                            : 'border-[#ead8b6] bg-[#fff8e9]',
                    ]"
                >
                    <UserRound
                        class="mt-0.5 size-4 shrink-0"
                        :class="
                            approvalMode === 'automatic'
                                ? 'text-[#39705d]'
                                : 'text-[#a56739]'
                        "
                    />
                    <div>
                        <p
                            class="text-xs font-semibold"
                            :class="
                                approvalMode === 'automatic'
                                    ? 'text-[#31594c]'
                                    : 'text-[#75452f]'
                            "
                        >
                            {{
                                approvalMode === 'automatic'
                                    ? '本团采用自动参团'
                                    : '本团需要团长审核'
                            }}
                        </p>
                        <p class="mt-1 text-[10px] leading-5 text-[#7f8179]">
                            {{
                                approvalMode === 'automatic'
                                    ? '提交后若名额充足，将立即获得正式参团资格。'
                                    : '提交后进入待审核状态，由团长确认后获得参团资格。'
                            }}
                        </p>
                    </div>
                </div>

                <div
                    class="mt-4 overflow-hidden rounded-2xl border border-[#e3c58f] bg-[#fff8e9]"
                >
                    <div
                        class="flex items-center justify-between gap-4 border-b border-[#ead8b6] px-4 py-3"
                    >
                        <span
                            class="inline-flex items-center gap-2 text-xs font-semibold text-[#70432d]"
                        >
                            <CircleDollarSign class="size-4" />费用提示
                        </span>
                        <span class="text-xs font-semibold text-[#a45435]">
                            工会补助 {{ dailySubsidy }} 元/人/天
                        </span>
                    </div>
                    <div class="p-4">
                        <p
                            v-if="estimatedSubsidy"
                            class="text-xs leading-5 text-[#795a48]"
                        >
                            本线路工会补助参考总额：每人
                            <strong>{{ estimatedSubsidy }} 元</strong>。
                        </p>
                        <div
                            v-if="selfFundedItems.length"
                            class="mt-3 flex items-start gap-2 text-[10px] leading-5 text-[#8f6c56]"
                        >
                            <Plane class="mt-0.5 size-3.5 shrink-0" />
                            <span>
                                个人自理：{{
                                    selfFundedItems.join('、')
                                }}。请确认后再提交报名。
                            </span>
                        </div>
                    </div>
                </div>

                <div
                    class="mt-4 flex items-center justify-between rounded-2xl bg-[#edf3ee] p-4"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="grid size-10 place-items-center rounded-full bg-[#1d4b3e] text-white"
                        >
                            <UserRound class="size-4" />
                        </div>
                        <div>
                            <p class="text-xs text-[#78817b]">参团本人</p>
                            <p
                                class="mt-0.5 text-sm font-semibold text-[#294c41]"
                            >
                                {{ page.props.auth.user.name }}
                            </p>
                        </div>
                    </div>
                    <span
                        class="rounded-full bg-white px-3 py-1 text-[10px] font-semibold text-[#396052]"
                        >已计入 1 人</span
                    >
                </div>

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
                        placeholder="成团、未成团或取消时用于接收短信"
                    />
                    <p class="mt-2 text-[10px] leading-5 text-[#8c8b82]">
                        仅用于本次疗休养状态通知和最终参团确认提醒。
                    </p>
                </label>

                <div class="mt-6 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-semibold text-[#315348]">
                            随行家属
                        </h3>
                        <p class="mt-1 text-[10px] text-[#8c8b82]">
                            不携带家属可直接提交
                        </p>
                    </div>
                    <button
                        type="button"
                        :disabled="!canAddFamily"
                        class="inline-flex h-9 items-center gap-1.5 rounded-full border border-[#cfcabd] px-4 text-xs font-semibold text-[#42685b] disabled:cursor-not-allowed disabled:opacity-40"
                        @click="addFamilyMember"
                    >
                        <Plus class="size-3.5" /> 添加家属
                    </button>
                </div>

                <div v-if="familyMembers.length" class="mt-4 space-y-3">
                    <div
                        v-for="(member, index) in familyMembers"
                        :key="index"
                        class="grid gap-3 rounded-2xl border border-[#e2ded2] bg-[#faf8f1] p-4 sm:grid-cols-[1fr_150px_auto] sm:items-end"
                    >
                        <label class="block">
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
                        <label class="block">
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
                    当前仅本人参团
                </div>

                <label class="mt-6 block">
                    <span
                        class="mb-2 block text-xs font-semibold text-[#315348]"
                        >给团长的留言</span
                    >
                    <textarea
                        v-model="message"
                        rows="3"
                        maxlength="1000"
                        class="w-full resize-none rounded-2xl border border-[#d9d4c7] bg-[#faf9f3] p-4 text-sm outline-none focus:border-[#4e7568]"
                        placeholder="可填写健康、住宿或其他需要团长了解的情况"
                    />
                </label>

                <div
                    class="mt-6 flex flex-col gap-3 border-t border-[#e5e0d4] pt-5 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div
                        class="inline-flex items-center gap-2 text-sm text-[#3d5e53]"
                    >
                        <UsersRound class="size-4 text-[#b65f40]" />
                        本次申请共
                        <strong class="font-serif-cn text-xl text-[#b65f40]">{{
                            totalPeople
                        }}</strong>
                        人
                    </div>
                    <button
                        type="submit"
                        :disabled="processing"
                        class="h-11 rounded-full bg-[#c46140] px-7 text-sm font-semibold text-white transition hover:bg-[#b45637] disabled:opacity-50"
                    >
                        {{ processing ? '正在提交…' : '提交参团申请' }}
                    </button>
                </div>
            </form>
        </DialogContent>
    </Dialog>
</template>
