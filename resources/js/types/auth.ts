export type User = {
    id: number;
    name: string;
    email: string;
    staff_number?: string | null;
    identity_source?: 'local' | 'cas';
    retreat_eligible?: boolean;
    directory_email?: string | null;
    mobile?: string | null;
    role?: 'admin' | 'department_approver' | 'union_approver' | 'teacher';
    group_roles?: (
        'admin' | 'group_department_reviewer' | 'group_final_reviewer'
    )[];
    is_admin?: boolean;
    can_route_approve?: boolean;
    can_group_approve?: boolean;
    department?: string | null;
    avatar?: string;
    email_verified_at: string | null;
    /* @chisel-2fa */
    two_factor_enabled?: boolean;
    /* @end-chisel-2fa */
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type Auth = {
    user: User;
};

/* @chisel-passkeys */
export type Passkey = {
    id: number;
    name: string;
    authenticator: string | null;
    created_at_diff: string;
    last_used_at_diff: string | null;
};
/* @end-chisel-passkeys */

/* @chisel-2fa */
export type TwoFactorConfigContent = {
    title: string;
    description: string;
    buttonText: string;
};
/* @end-chisel-2fa */
