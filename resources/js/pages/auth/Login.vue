<script setup lang="ts">
import { Form, Head, usePage } from '@inertiajs/vue3';
import {
    ArrowRight,
    BadgeCheck,
    Building2,
    KeyRound,
    ShieldCheck,
} from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

defineOptions({
    layout: {
        title: '高校教职工疗休养',
        description: '统一身份认证与教师资格核验',
    },
});

defineProps<{
    status?: string;
    canResetPassword: boolean;
    casEnabled: boolean;
    casLoginUrl: string;
}>();

const page = usePage();
</script>

<template>
    <Head title="登录" />

    <div
        class="overflow-hidden rounded-[28px] border border-[#ddd8ca] bg-[#fffefa] shadow-[0_24px_70px_rgba(31,63,52,.12)]"
    >
        <div class="relative overflow-hidden bg-[#173f35] px-6 py-7 text-white">
            <div
                class="absolute -top-12 -right-10 size-40 rounded-full border border-white/10"
            />
            <div
                class="absolute top-8 -right-4 size-20 rounded-full border border-[#e7bb76]/30"
            />
            <div class="relative">
                <span
                    class="inline-flex items-center gap-2 text-[10px] font-semibold tracking-[.2em] text-[#e8bf7e] uppercase"
                >
                    <Building2 class="size-3.5" /> Faculty journey
                </span>
                <h2 class="font-serif-cn mt-3 text-2xl font-semibold">
                    从校园出发，安心去休养
                </h2>
                <p class="mt-2 text-xs leading-6 text-white/60">
                    认证身份后可申报线路、发起组团、携带家属并完成最终参团确认。
                </p>
            </div>
        </div>

        <div class="p-6">
            <div
                v-if="status"
                class="mb-4 rounded-2xl bg-[#e8f0ea] p-3 text-xs font-medium text-[#31594c]"
            >
                {{ status }}
            </div>
            <div
                v-if="page.props.errors?.cas"
                class="mb-4 rounded-2xl border border-[#ecc9bb] bg-[#fff1eb] p-3 text-xs leading-5 text-[#954e36]"
            >
                {{ page.props.errors.cas }}
            </div>

            <a
                v-if="casEnabled"
                :href="casLoginUrl"
                class="group flex min-h-14 w-full items-center justify-between rounded-2xl bg-[#c46140] px-5 text-sm font-semibold text-white transition hover:-translate-y-0.5 hover:bg-[#b75637]"
            >
                <span class="flex items-center gap-3">
                    <span
                        class="grid size-9 place-items-center rounded-full bg-white/15"
                    >
                        <ShieldCheck class="size-4" />
                    </span>
                    使用学校统一身份认证
                </span>
                <ArrowRight
                    class="size-4 transition group-hover:translate-x-1"
                />
            </a>
            <div
                v-else
                class="rounded-2xl border border-dashed border-[#d6cdbd] bg-[#f7f4eb] p-4 text-xs leading-6 text-[#756e62]"
            >
                <div
                    class="flex items-center gap-2 font-semibold text-[#315348]"
                >
                    <ShieldCheck
                        class="size-4 text-[#b65f40]"
                    />统一身份认证等待配置
                </div>
                <p class="mt-1">
                    CAS 服务地址和正式回调地址配置完成后，此处将开放教师登录。
                </p>
            </div>

            <div
                class="mt-4 flex items-start gap-3 rounded-2xl bg-[#edf3ee] p-4"
            >
                <BadgeCheck class="mt-0.5 size-4 shrink-0 text-[#39705d]" />
                <p class="text-[10px] leading-5 text-[#607168]">
                    系统会自动核验教师资格清单，仅
                    <strong>人员类型为教师</strong>
                    的在职账号可以进入疗休养业务。
                </p>
            </div>

            <details class="group mt-5 border-t border-[#e5e0d4] pt-5">
                <summary
                    class="flex cursor-pointer list-none items-center justify-between text-xs font-semibold text-[#52675f]"
                >
                    <span class="inline-flex items-center gap-2"
                        ><KeyRound
                            class="size-4 text-[#aa6246]"
                        />本地管理员登录</span
                    >
                    <span
                        class="text-[10px] font-normal text-[#969087] group-open:hidden"
                        >展开</span
                    >
                    <span
                        class="hidden text-[10px] font-normal text-[#969087] group-open:inline"
                        >收起</span
                    >
                </summary>

                <Form
                    v-bind="store.form()"
                    :reset-on-success="['password']"
                    v-slot="{ errors, processing }"
                    class="mt-5 flex flex-col gap-4"
                >
                    <label class="grid gap-2">
                        <Label for="email" class="text-xs">管理员邮箱</Label>
                        <Input
                            id="email"
                            type="email"
                            name="email"
                            required
                            autocomplete="email"
                            placeholder="admin@lxy.edu.cn"
                        />
                        <InputError :message="errors.email" />
                    </label>
                    <label class="grid gap-2">
                        <div class="flex items-center justify-between">
                            <Label for="password" class="text-xs">密码</Label>
                            <TextLink
                                v-if="canResetPassword"
                                :href="request()"
                                class="text-[10px]"
                                >忘记密码</TextLink
                            >
                        </div>
                        <PasswordInput
                            id="password"
                            name="password"
                            required
                            autocomplete="current-password"
                            placeholder="请输入管理员密码"
                        />
                        <InputError :message="errors.password" />
                    </label>
                    <div class="flex items-center justify-between">
                        <Label
                            for="remember"
                            class="flex items-center gap-2 text-[10px] text-[#777d76]"
                        >
                            <Checkbox id="remember" name="remember" />保持登录
                        </Label>
                    </div>
                    <Button
                        type="submit"
                        class="h-11 w-full rounded-full bg-[#244a3d] hover:bg-[#173f35]"
                        :disabled="processing"
                        data-test="login-button"
                    >
                        <Spinner v-if="processing" />进入管理后台
                    </Button>
                </Form>
            </details>
        </div>
    </div>
</template>
