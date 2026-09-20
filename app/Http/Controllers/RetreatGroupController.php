<?php

namespace App\Http\Controllers;

use App\Models\RetreatGroup;
use App\Models\RetreatGroupApplication;
use App\Models\RetreatGroupReview;
use App\Models\RetreatRoute;
use App\Models\User;
use App\Services\RetreatGroupLifecycleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RetreatGroupController extends Controller
{
    public function index(Request $request): Response
    {
        $groups = RetreatGroup::query()
            ->with([
                'route',
                'route.reviews' => fn ($query) => $query->where('user_id', $request->user()->id),
                'leader',
                'applications' => fn ($query) => $query->where('user_id', $request->user()->id),
            ])
            ->withSum(['applications as joined_count' => fn ($query) => $query->where('status', 'approved')], 'member_count')
            ->latest()
            ->get()
            ->map(fn (RetreatGroup $group) => $this->serializeGroup($group, $request->user()->id));

        return Inertia::render('retreat/Groups', ['groupRecords' => $groups]);
    }

    public function create(Request $request): Response
    {
        $route = RetreatRoute::query()
            ->where('status', 'approved')
            ->findOrFail($request->integer('route'));

        return Inertia::render('retreat/CreateGroup', [
            'routeId' => $route->id,
            'routeRecord' => $this->serializeRoute($route),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'retreat_route_id' => ['required', 'integer', 'exists:retreat_routes,id'],
            'title' => ['required', 'string', 'max:120'],
            'departure_date' => ['required', 'date'],
            'return_date' => ['required', 'date', 'after_or_equal:departure_date'],
            'application_deadline' => ['required', 'date', 'before:departure_date'],
            'min_people' => ['required', 'integer', 'min:1'],
            'max_people' => ['required', 'integer', 'gt:min_people'],
            'approval_mode' => ['required', 'in:automatic,manual'],
            'meeting_info' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'contact_mobile' => ['required', 'regex:/^1[3-9]\d{9}$/'],
            'attachment' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'wechat_qr_code' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $attachment = $request->file('attachment');
        $wechatQrCode = $request->file('wechat_qr_code');
        unset($validated['attachment'], $validated['wechat_qr_code']);
        $contactMobile = $validated['contact_mobile'];
        unset($validated['contact_mobile']);

        $route = RetreatRoute::where('status', 'approved')->findOrFail($validated['retreat_route_id']);
        if ($validated['min_people'] < $route->min_people || $validated['max_people'] > $route->max_people) {
            throw ValidationException::withMessages([
                'max_people' => "组团人数必须在已审批范围 {$route->min_people}—{$route->max_people} 人内。",
            ]);
        }

        $group = DB::transaction(function () use ($request, $validated, $contactMobile): RetreatGroup {
            $group = RetreatGroup::create([
                ...$validated,
                'leader_id' => $request->user()->id,
                'status' => 'open',
            ]);

            $group->applications()->create([
                'user_id' => $request->user()->id,
                'member_count' => 1,
                'family_members' => [],
                'message' => '团长创建组团时自动加入',
                'contact_mobile' => $contactMobile,
                'status' => 'approved',
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'review_comment' => '系统自动确认团长成员资格',
            ]);

            return $group;
        });

        if ($attachment) {
            $group->update([
                'attachment_path' => $attachment->store("retreat-groups/{$group->id}"),
                'attachment_name' => $attachment->getClientOriginalName(),
            ]);
        }

        if ($wechatQrCode) {
            $group->update([
                'wechat_qr_code_path' => $wechatQrCode->store("retreat-groups/{$group->id}/wechat"),
                'wechat_qr_code_name' => $wechatQrCode->getClientOriginalName(),
            ]);
        }

        return to_route('retreat.groups.show', $group)->with('success', '组团已发布，您已作为团长自动加入');
    }

    public function edit(Request $request, RetreatGroup $retreatGroup): Response
    {
        $this->authorizeGroupManager($request, $retreatGroup);
        $retreatGroup->load(['route', 'leader', 'applications']);
        $retreatGroup->loadSum(['applications as joined_count' => fn ($query) => $query->where('status', 'approved')], 'member_count');

        return Inertia::render('retreat/CreateGroup', [
            'routeId' => $retreatGroup->retreat_route_id,
            'routeRecord' => $this->serializeRoute($retreatGroup->route),
            'groupRecord' => $this->serializeGroup($retreatGroup, $request->user()->id, true),
            'editMode' => true,
        ]);
    }

    public function update(Request $request, RetreatGroup $retreatGroup): RedirectResponse
    {
        $this->authorizeGroupManager($request, $retreatGroup);
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'departure_date' => ['required', 'date'],
            'return_date' => ['required', 'date', 'after_or_equal:departure_date'],
            'application_deadline' => ['required', 'date', 'before:departure_date'],
            'min_people' => ['required', 'integer', 'min:1'],
            'max_people' => ['required', 'integer', 'gt:min_people'],
            'approval_mode' => ['required', 'in:automatic,manual'],
            'meeting_info' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'contact_mobile' => ['nullable', 'regex:/^1[3-9]\d{9}$/'],
            'attachment' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'wechat_qr_code' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $route = $retreatGroup->route;
        if ($validated['approval_mode'] !== $retreatGroup->approval_mode) {
            throw ValidationException::withMessages([
                'approval_mode' => '组团发布后不能切换报名确认方式。',
            ]);
        }
        if ($validated['min_people'] < $route->min_people || $validated['max_people'] > $route->max_people) {
            throw ValidationException::withMessages([
                'max_people' => "组团人数必须在已审批范围 {$route->min_people}—{$route->max_people} 人内。",
            ]);
        }

        $joined = (int) $retreatGroup->applications()->where('status', 'approved')->sum('member_count');
        if ($validated['max_people'] < $joined) {
            throw ValidationException::withMessages([
                'max_people' => "最高人数不能低于当前已确认的 {$joined} 人。",
            ]);
        }

        $attachment = $request->file('attachment');
        $wechatQrCode = $request->file('wechat_qr_code');
        $contactMobile = $validated['contact_mobile'] ?? null;
        unset($validated['attachment'], $validated['wechat_qr_code'], $validated['contact_mobile']);
        $retreatGroup->update($validated);

        if ($contactMobile) {
            $retreatGroup->applications()
                ->where('user_id', $retreatGroup->leader_id)
                ->update(['contact_mobile' => $contactMobile]);
        }

        if ($attachment) {
            if ($retreatGroup->attachment_path) {
                Storage::disk('local')->delete($retreatGroup->attachment_path);
            }
            $retreatGroup->update([
                'attachment_path' => $attachment->store("retreat-groups/{$retreatGroup->id}"),
                'attachment_name' => $attachment->getClientOriginalName(),
            ]);
        }

        if ($wechatQrCode) {
            if ($retreatGroup->wechat_qr_code_path) {
                Storage::disk('local')->delete($retreatGroup->wechat_qr_code_path);
            }
            $retreatGroup->update([
                'wechat_qr_code_path' => $wechatQrCode->store("retreat-groups/{$retreatGroup->id}/wechat"),
                'wechat_qr_code_name' => $wechatQrCode->getClientOriginalName(),
            ]);
        }

        return to_route('retreat.groups.show', $retreatGroup)->with('success', '组团活动信息已更新');
    }

    public function downloadAttachment(Request $request, RetreatGroup $retreatGroup): StreamedResponse
    {
        abort_unless($retreatGroup->attachment_path, 404);
        abort_unless(Storage::disk('local')->exists($retreatGroup->attachment_path), 404);

        return Storage::disk('local')->download(
            $retreatGroup->attachment_path,
            $retreatGroup->attachment_name ?: '疗休养活动方案.pdf',
            ['Content-Type' => 'application/pdf'],
        );
    }

    public function showWechatQrCode(Request $request, RetreatGroup $retreatGroup): StreamedResponse
    {
        $canView = $request->user()->isRetreatAdmin()
            || $retreatGroup->leader_id === $request->user()->id
            || $retreatGroup->applications()
                ->where('user_id', $request->user()->id)
                ->where('status', 'approved')
                ->exists();

        abort_unless($canView, 403);
        abort_unless($retreatGroup->wechat_qr_code_path, 404);
        abort_unless(Storage::disk('local')->exists($retreatGroup->wechat_qr_code_path), 404);

        return Storage::disk('local')->response(
            $retreatGroup->wechat_qr_code_path,
            $retreatGroup->wechat_qr_code_name ?: '微信群二维码',
            ['Cache-Control' => 'private, max-age=300'],
            'inline',
        );
    }

    public function show(Request $request, RetreatGroup $retreatGroup): Response
    {
        $retreatGroup->load(['route.itineraryDays', 'leader', 'applications.user', 'reviews.user', 'smsNotifications']);
        $retreatGroup->loadSum(['applications as joined_count' => fn ($query) => $query->where('status', 'approved')], 'member_count');
        $canManage = $request->user()->isRetreatAdmin() || $retreatGroup->leader_id === $request->user()->id;
        $leaderApplication = $retreatGroup->applications->firstWhere('user_id', $retreatGroup->leader_id);

        return Inertia::render('retreat/GroupDetail', [
            'groupId' => $retreatGroup->id,
            'groupRecord' => $this->serializeGroup($retreatGroup, $request->user()->id, $canManage),
            'routeRecord' => $this->serializeRoute($retreatGroup->route),
            'itineraryRecord' => $retreatGroup->route->itineraryDays->map(fn ($day) => [
                'day' => $day->day_number,
                'title' => $day->title,
                'location' => $day->location,
                'period' => $day->transport ?: '全天',
                'plan' => collect([$day->morning, $day->afternoon, $day->evening, $day->plan])->filter()->join('；'),
                'stay' => $day->stay ?: '待定',
                'note' => collect([$day->meals, $day->note])->filter()->join('；'),
            ]),
            'currentApplication' => $retreatGroup->applications
                ->firstWhere('user_id', $request->user()->id)?->only([
                    'id',
                    'status',
                    'member_count',
                    'contact_mobile',
                    'final_confirmation_status',
                    'final_confirmation_at',
                ]),
            'canManage' => $canManage,
            'isLeader' => $retreatGroup->leader_id === $request->user()->id,
            'leaderApplication' => $leaderApplication ? [
                'id' => $leaderApplication->id,
                'memberCount' => $leaderApplication->member_count,
                'familyMembers' => $leaderApplication->family_members ?? [],
                'contactMobile' => $leaderApplication->contact_mobile,
            ] : null,
            'availableMembers' => $canManage && $retreatGroup->status === 'open'
                ? User::query()
                    ->whereNotIn('id', $retreatGroup->applications->pluck('user_id'))
                    ->whereNotNull('email_verified_at')
                    ->orderBy('name')
                    ->limit(200)
                    ->get(['id', 'name', 'department', 'email'])
                    ->map(fn (User $user) => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'department' => $user->department ?: '未设置单位',
                        'email' => $user->email,
                    ])
                : [],
            'applications' => $retreatGroup->applications
                ->where('user_id', '!=', $retreatGroup->leader_id)
                ->values()
                ->map(fn (RetreatGroupApplication $application) => [
                    'id' => $application->id,
                    'name' => $application->user->name,
                    'department' => $application->user->department ?: '未设置单位',
                    'memberCount' => $application->member_count,
                    'familyMembers' => $application->family_members ?? [],
                    'status' => $application->status,
                    'contactMobile' => $application->contact_mobile,
                    'finalConfirmationStatus' => $application->final_confirmation_status,
                    'finalConfirmationAt' => $application->final_confirmation_at?->format('Y-m-d H:i'),
                    'message' => $application->message,
                    'manuallyAdded' => $application->review_comment === '团长手动添加成员',
                    'submittedAt' => $application->created_at->diffForHumans(),
                ]),
            'smsSummary' => [
                'total' => $retreatGroup->smsNotifications->count(),
                'pendingProvider' => $retreatGroup->smsNotifications->where('status', 'pending_provider')->count(),
                'missingMobile' => $retreatGroup->smsNotifications->where('status', 'missing_mobile')->count(),
                'sent' => $retreatGroup->smsNotifications->where('status', 'sent')->count(),
                'provider' => '中国移动（待接口文档接入）',
            ],
            'canReview' => $this->canReview($retreatGroup, $request->user()->id),
            'currentReview' => $retreatGroup->reviews
                ->firstWhere('user_id', $request->user()->id)
                ?->only([
                    'id',
                    'route_score',
                    'meal_score',
                    'attraction_score',
                    'accommodation_score',
                    'service_score',
                    'comment',
                ]),
            'reviewSummary' => $this->reviewSummary($retreatGroup),
            'reviewRecords' => $retreatGroup->reviews
                ->sortByDesc('updated_at')
                ->values()
                ->map(fn (RetreatGroupReview $review) => [
                    'id' => $review->id,
                    'name' => $review->user->name,
                    'department' => $review->user->department ?: '未设置单位',
                    'overallScore' => $review->overallScore(),
                    'comment' => $review->comment,
                    'reviewedAt' => $review->updated_at->format('Y-m-d'),
                ]),
        ]);
    }

    public function apply(Request $request, RetreatGroup $retreatGroup): RedirectResponse
    {
        abort_if($retreatGroup->leader_id === $request->user()->id, 422, '团长已自动加入该团队。');
        abort_unless(
            $retreatGroup->status === 'open' && ! $retreatGroup->application_deadline->isPast(),
            422,
        );
        $validated = $request->validate([
            'member_count' => ['required', 'integer', 'min:1', 'max:10'],
            'family_members' => ['present', 'array', 'max:9'],
            'family_members.*.name' => ['required', 'string', 'max:50'],
            'family_members.*.relationship' => ['required', 'string', 'max:30'],
            'contact_mobile' => ['required', 'regex:/^1[3-9]\d{9}$/'],
            'message' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($validated['member_count'] !== count($validated['family_members']) + 1) {
            throw ValidationException::withMessages([
                'member_count' => '参团人数必须等于本人加随行家属人数。',
            ]);
        }

        $joined = (int) $retreatGroup->applications()->where('status', 'approved')->sum('member_count');
        if ($joined + $validated['member_count'] > $retreatGroup->max_people) {
            throw ValidationException::withMessages([
                'member_count' => '本人及家属人数超过当前剩余名额。',
            ]);
        }

        $existing = $retreatGroup->applications()->where('user_id', $request->user()->id)->first();
        abort_if($existing && in_array($existing->status, ['pending', 'approved'], true), 422);

        $applicationStatus = $retreatGroup->approval_mode === 'automatic' ? 'approved' : 'pending';
        RetreatGroupApplication::updateOrCreate(
            ['retreat_group_id' => $retreatGroup->id, 'user_id' => $request->user()->id],
            [
                ...$validated,
                'status' => $applicationStatus,
                'reviewed_by' => $applicationStatus === 'approved' ? $retreatGroup->leader_id : null,
                'reviewed_at' => $applicationStatus === 'approved' ? now() : null,
                'review_comment' => $applicationStatus === 'approved' ? '按组团设置自动通过' : null,
            ],
        );

        return back()->with('success', $applicationStatus === 'approved'
            ? '报名成功，已自动确认参团资格'
            : '参团申请已提交，等待团长审核');
    }

    public function addMember(Request $request, RetreatGroup $retreatGroup): RedirectResponse
    {
        $this->authorizeGroupManager($request, $retreatGroup);
        abort_unless($retreatGroup->status === 'open', 422, '仅报名中的团队可以添加成员。');

        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'contact_mobile' => ['required', 'regex:/^1[3-9]\d{9}$/'],
            'family_members' => ['present', 'array', 'max:9'],
            'family_members.*.name' => ['required', 'string', 'max:50'],
            'family_members.*.relationship' => ['required', 'string', 'max:30'],
        ]);

        $memberCount = count($validated['family_members']) + 1;

        DB::transaction(function () use ($request, $retreatGroup, $validated, $memberCount): void {
            $group = RetreatGroup::query()->lockForUpdate()->findOrFail($retreatGroup->id);
            abort_unless($group->status === 'open', 422, '仅报名中的团队可以添加成员。');

            if ($group->applications()->where('user_id', $validated['user_id'])->exists()) {
                throw ValidationException::withMessages([
                    'user_id' => '该教职工已经提交过本团申请或已经在团内。',
                ]);
            }

            $joined = (int) $group->applications()->where('status', 'approved')->sum('member_count');
            if ($joined + $memberCount > $group->max_people) {
                throw ValidationException::withMessages([
                    'family_members' => '新增人员超过当前剩余名额。',
                ]);
            }

            $group->applications()->create([
                'user_id' => $validated['user_id'],
                'member_count' => $memberCount,
                'family_members' => $validated['family_members'],
                'message' => '由团长直接加入团队',
                'contact_mobile' => $validated['contact_mobile'],
                'status' => 'approved',
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'review_comment' => '团长手动添加成员',
            ]);
        });

        return back()->with('success', '团员已添加并自动通过，可登录系统查看和确认行程');
    }

    public function updateLeaderFamily(Request $request, RetreatGroup $retreatGroup): RedirectResponse
    {
        $this->authorizeGroupManager($request, $retreatGroup);
        abort_unless($retreatGroup->status === 'open', 422, '仅报名中的团队可以调整团长家属。');

        $validated = $request->validate([
            'family_members' => ['present', 'array', 'max:9'],
            'family_members.*.name' => ['required', 'string', 'max:50'],
            'family_members.*.relationship' => ['required', 'string', 'max:30'],
        ]);

        DB::transaction(function () use ($retreatGroup, $validated): void {
            $group = RetreatGroup::query()->lockForUpdate()->findOrFail($retreatGroup->id);
            abort_unless($group->status === 'open', 422, '仅报名中的团队可以调整团长家属。');

            $leaderApplication = $group->applications()->where('user_id', $group->leader_id)->firstOrFail();
            $memberCount = count($validated['family_members']) + 1;
            $otherJoined = (int) $group->applications()
                ->where('status', 'approved')
                ->where('id', '!=', $leaderApplication->id)
                ->sum('member_count');

            if ($otherJoined + $memberCount > $group->max_people) {
                throw ValidationException::withMessages([
                    'family_members' => '团长本人及家属人数超过当前剩余名额。',
                ]);
            }

            $leaderApplication->update([
                'member_count' => $memberCount,
                'family_members' => $validated['family_members'],
            ]);
        });

        return back()->with('success', '团长随行家属已更新');
    }

    public function reviewApplication(
        Request $request,
        RetreatGroup $retreatGroup,
        RetreatGroupApplication $application,
    ): RedirectResponse {
        abort_unless($request->user()->isRetreatAdmin() || $retreatGroup->leader_id === $request->user()->id, 403);
        abort_unless($application->retreat_group_id === $retreatGroup->id, 404);
        $validated = $request->validate([
            'action' => ['required', 'in:approved,rejected'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($validated['action'] === 'approved') {
            $joined = (int) $retreatGroup->applications()->where('status', 'approved')->sum('member_count');
            if ($joined + $application->member_count > $retreatGroup->max_people) {
                throw ValidationException::withMessages(['action' => '通过后将超过组团人数上限。']);
            }
        }

        $application->update([
            'status' => $validated['action'],
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'review_comment' => $validated['comment'] ?? null,
        ]);

        return back()->with('success', $validated['action'] === 'approved' ? '报名申请已通过' : '报名申请已拒绝');
    }

    public function changeStatus(
        Request $request,
        RetreatGroup $retreatGroup,
        RetreatGroupLifecycleService $lifecycle,
    ): RedirectResponse {
        $this->authorizeGroupManager($request, $retreatGroup);
        $validated = $request->validate([
            'action' => ['required', 'in:formed,failed,cancelled'],
            'reason' => ['nullable', 'required_if:action,failed,cancelled', 'string', 'max:1000'],
        ]);

        $queued = match ($validated['action']) {
            'formed' => $lifecycle->form($retreatGroup),
            'failed' => $lifecycle->fail($retreatGroup, $validated['reason']),
            'cancelled' => $lifecycle->cancel($retreatGroup, $validated['reason']),
        };

        $message = match ($validated['action']) {
            'formed' => '团队已成团，最终确认短信任务已生成',
            'failed' => '团队已标记为未成团，通知短信任务已生成',
            'cancelled' => '团队已取消，通知短信任务已生成',
        };

        return back()->with('success', "{$message}，共 {$queued} 位接收人");
    }

    public function remindFinalConfirmation(
        Request $request,
        RetreatGroup $retreatGroup,
        RetreatGroupLifecycleService $lifecycle,
    ): RedirectResponse {
        $this->authorizeGroupManager($request, $retreatGroup);
        $queued = $lifecycle->remind($retreatGroup);

        return back()->with('success', "已为 {$queued} 位待确认团员生成短信提醒任务");
    }

    public function confirmParticipation(Request $request, RetreatGroup $retreatGroup): RedirectResponse
    {
        abort_unless($retreatGroup->status === 'formed', 422, '当前团队无需最终确认。');
        $validated = $request->validate(['action' => ['required', 'in:confirmed,declined']]);
        $application = $retreatGroup->applications()
            ->where('user_id', $request->user()->id)
            ->where('status', 'approved')
            ->firstOrFail();
        abort_if($application->user_id === $retreatGroup->leader_id, 422, '团长已自动完成最终确认。');

        $application->update([
            'final_confirmation_status' => $validated['action'],
            'final_confirmation_at' => now(),
        ]);

        return back()->with('success', $validated['action'] === 'confirmed'
            ? '最终参团确认已提交'
            : '已记录无法参团，请及时联系团长');
    }

    public function storeReview(Request $request, RetreatGroup $retreatGroup): RedirectResponse
    {
        abort_unless($retreatGroup->return_date->lt(today()), 422, '行程结束后才能提交评价。');
        abort_unless(
            $retreatGroup->applications()
                ->where('user_id', $request->user()->id)
                ->where('status', 'approved')
                ->exists(),
            403,
        );

        $validated = $request->validate([
            'route_score' => ['required', 'integer', 'between:1,5'],
            'meal_score' => ['required', 'integer', 'between:1,5'],
            'attraction_score' => ['required', 'integer', 'between:1,5'],
            'accommodation_score' => ['required', 'integer', 'between:1,5'],
            'service_score' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $retreatGroup->reviews()->updateOrCreate(
            ['user_id' => $request->user()->id],
            $validated,
        );

        return back()->with('success', '感谢您的评价，本次体验反馈已保存');
    }

    private function serializeGroup(
        RetreatGroup $group,
        ?int $currentUserId = null,
        bool $canManage = false,
    ): array
    {
        $currentApplication = $group->relationLoaded('applications')
            ? $group->applications->firstWhere('user_id', $currentUserId)
            : null;

        return [
            'id' => $group->id,
            'title' => $group->title,
            'route' => $group->route->title,
            'routeId' => $group->route->id,
            'location' => $group->route->location,
            'region' => $group->route->region ?: '其他',
            'days' => $group->route->days,
            'leader' => $group->leader->name,
            'leaderId' => $group->leader_id,
            'isLeader' => $group->leader_id === $currentUserId,
            'membershipStatus' => $currentApplication?->status,
            'contactMobile' => $currentApplication?->contact_mobile,
            'finalConfirmationStatus' => $currentApplication?->final_confirmation_status,
            'reviewed' => $group->route->relationLoaded('reviews')
                ? $group->route->reviews->contains('user_id', $currentUserId)
                : false,
            'canReview' => $this->canReview($group, $currentUserId),
            'department' => $group->leader->department ?: '未设置单位',
            'date' => $group->departure_date->format('m月d日').'—'.$group->return_date->format('m月d日'),
            'departureDate' => $group->departure_date->toDateString(),
            'returnDate' => $group->return_date->toDateString(),
            'applicationDeadline' => $group->application_deadline->toDateString(),
            'deadline' => $group->application_deadline->isPast()
                ? '已截止'
                : '还剩 '.now()->startOfDay()->diffInDays($group->application_deadline).' 天',
            'joined' => (int) ($group->joined_count ?? 0),
            'capacity' => $group->max_people,
            'min' => $group->min_people,
            'approvalMode' => $group->approval_mode ?: 'manual',
            'status' => match ($group->status) {
                'cancelled' => '已取消',
                'failed' => '未成团',
                default => $group->return_date->lt(today())
                    ? '已结束'
                    : match ($group->status) {
                        'formed' => '已成团',
                        default => $group->application_deadline->isPast()
                            ? '已截止'
                            : (((int) ($group->joined_count ?? 0)) >= $group->max_people - 2 ? '即将满员' : '报名中'),
                    },
            },
            'rawStatus' => $group->status,
            'statusReason' => $group->status_reason,
            'finalConfirmationDeadline' => $group->final_confirmation_deadline?->format('Y-m-d H:i'),
            'confirmationCounts' => $group->relationLoaded('applications') ? [
                'pending' => $group->applications->where('final_confirmation_status', 'pending')->sum('member_count'),
                'confirmed' => $group->applications->where('final_confirmation_status', 'confirmed')->sum('member_count'),
                'declined' => $group->applications->where('final_confirmation_status', 'declined')->sum('member_count'),
            ] : null,
            'palette' => 'from-[#1c514b] to-[#789987]',
            'meetingInfo' => $group->meeting_info,
            'notes' => $group->notes,
            'attachmentName' => $group->attachment_name,
            'attachmentUrl' => $group->attachment_path
                ? route('retreat.groups.attachment', $group)
                : null,
            'wechatQrCodeName' => $group->wechat_qr_code_name,
            'wechatQrCodeUrl' => $group->wechat_qr_code_path
                && ($canManage || $currentApplication?->status === 'approved')
                    ? route('retreat.groups.wechat-qr-code', $group)
                    : null,
        ];
    }

    private function authorizeGroupManager(Request $request, RetreatGroup $group): void
    {
        abort_unless(
            $request->user()->isRetreatAdmin() || $group->leader_id === $request->user()->id,
            403,
        );
    }

    private function canReview(RetreatGroup $group, ?int $userId): bool
    {
        if (! $userId || ! $group->return_date->lt(today())) {
            return false;
        }

        if ($group->relationLoaded('applications')) {
            return $group->applications->contains(
                fn (RetreatGroupApplication $application) => $application->user_id === $userId
                    && $application->status === 'approved',
            );
        }

        return $group->applications()
            ->where('user_id', $userId)
            ->where('status', 'approved')
            ->exists();
    }

    private function reviewSummary(RetreatGroup $group): array
    {
        $reviews = $group->reviews;

        return [
            'count' => $reviews->count(),
            'overall' => $reviews->isEmpty()
                ? null
                : round($reviews->average(fn (RetreatGroupReview $review) => $review->overallScore()), 1),
            'route' => $reviews->isEmpty() ? null : round($reviews->average('route_score'), 1),
            'meal' => $reviews->isEmpty() ? null : round($reviews->average('meal_score'), 1),
            'attraction' => $reviews->isEmpty() ? null : round($reviews->average('attraction_score'), 1),
            'accommodation' => $reviews->isEmpty() ? null : round($reviews->average('accommodation_score'), 1),
            'service' => $reviews->isEmpty() ? null : round($reviews->average('service_score'), 1),
        ];
    }

    private function serializeRoute(RetreatRoute $route): array
    {
        return [
            'id' => $route->id,
            'title' => $route->title,
            'location' => $route->location,
            'region' => $route->region ?: '其他',
            'days' => $route->days,
            'people' => "{$route->min_people}—{$route->max_people} 人",
            'summary' => $route->summary,
            'tags' => array_slice($route->highlights ?? ['疗休养'], 0, 3),
            'palette' => 'from-[#173f4a] via-[#32667a] to-[#91ad9d]',
            'accent' => '#f2d49b',
            'updatedAt' => $route->updated_at->format('m-d'),
            'favorite' => false,
            'cover' => $route->cover_path,
            'importedByUnion' => $route->retreat_route_import_id !== null,
            'highlights' => $route->highlights ?? [],
            'funding' => [
                'dailySubsidy' => 500,
                'estimatedSubsidy' => $route->days * 500,
                'selfFundedItems' => $route->self_funded_items ?? [
                    '往返大交通费用（如机票、高铁票）',
                    '个人消费及方案未列明项目',
                ],
                'disclaimer' => '工会按每人每天 500 元标准提供疗休养经费，超出补助范围及明确列示的项目由个人自行承担。',
            ],
        ];
    }
}
