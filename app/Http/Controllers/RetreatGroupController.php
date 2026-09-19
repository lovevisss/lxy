<?php

namespace App\Http\Controllers;

use App\Models\RetreatGroup;
use App\Models\RetreatGroupApplication;
use App\Models\RetreatRoute;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RetreatGroupController extends Controller
{
    public function index(Request $request): Response
    {
        $groups = RetreatGroup::query()
            ->with([
                'route',
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
            'meeting_info' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ]);

        $route = RetreatRoute::where('status', 'approved')->findOrFail($validated['retreat_route_id']);
        if ($validated['min_people'] < $route->min_people || $validated['max_people'] > $route->max_people) {
            throw ValidationException::withMessages([
                'max_people' => "组团人数必须在已审批范围 {$route->min_people}—{$route->max_people} 人内。",
            ]);
        }

        $group = DB::transaction(function () use ($request, $validated): RetreatGroup {
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
                'status' => 'approved',
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'review_comment' => '系统自动确认团长成员资格',
            ]);

            return $group;
        });

        return to_route('retreat.groups.show', $group)->with('success', '组团已发布，您已作为团长自动加入');
    }

    public function show(Request $request, RetreatGroup $retreatGroup): Response
    {
        $retreatGroup->load(['route.itineraryDays', 'leader', 'applications.user']);
        $retreatGroup->loadSum(['applications as joined_count' => fn ($query) => $query->where('status', 'approved')], 'member_count');

        return Inertia::render('retreat/GroupDetail', [
            'groupId' => $retreatGroup->id,
            'groupRecord' => $this->serializeGroup($retreatGroup, $request->user()->id),
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
                ->firstWhere('user_id', $request->user()->id)?->only(['id', 'status', 'member_count']),
            'canManage' => $request->user()->isRetreatAdmin() || $retreatGroup->leader_id === $request->user()->id,
            'isLeader' => $retreatGroup->leader_id === $request->user()->id,
            'applications' => $retreatGroup->applications
                ->where('user_id', '!=', $retreatGroup->leader_id)
                ->values()
                ->map(fn (RetreatGroupApplication $application) => [
                    'id' => $application->id,
                    'name' => $application->user->name,
                    'department' => $application->user->department ?: '未设置单位',
                    'memberCount' => $application->member_count,
                    'status' => $application->status,
                    'message' => $application->message,
                    'submittedAt' => $application->created_at->diffForHumans(),
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
            'family_members' => ['nullable', 'array'],
            'message' => ['nullable', 'string', 'max:1000'],
        ]);

        $existing = $retreatGroup->applications()->where('user_id', $request->user()->id)->first();
        abort_if($existing && in_array($existing->status, ['pending', 'approved'], true), 422);

        RetreatGroupApplication::updateOrCreate(
            ['retreat_group_id' => $retreatGroup->id, 'user_id' => $request->user()->id],
            [...$validated, 'status' => 'pending', 'reviewed_by' => null, 'reviewed_at' => null],
        );

        return back()->with('success', '参团申请已提交，等待团长审核');
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

    private function serializeGroup(RetreatGroup $group, ?int $currentUserId = null): array
    {
        $currentApplication = $group->relationLoaded('applications')
            ? $group->applications->firstWhere('user_id', $currentUserId)
            : null;

        return [
            'id' => $group->id,
            'title' => $group->title,
            'route' => $group->route->title,
            'routeId' => $group->route->id,
            'leader' => $group->leader->name,
            'leaderId' => $group->leader_id,
            'isLeader' => $group->leader_id === $currentUserId,
            'membershipStatus' => $currentApplication?->status,
            'department' => $group->leader->department ?: '未设置单位',
            'date' => $group->departure_date->format('m月d日').'—'.$group->return_date->format('m月d日'),
            'departureDate' => $group->departure_date->toDateString(),
            'returnDate' => $group->return_date->toDateString(),
            'deadline' => $group->application_deadline->isPast()
                ? '已截止'
                : '还剩 '.now()->startOfDay()->diffInDays($group->application_deadline).' 天',
            'joined' => (int) ($group->joined_count ?? 0),
            'capacity' => $group->max_people,
            'min' => $group->min_people,
            'status' => match ($group->status) {
                'formed' => '已成团',
                default => $group->application_deadline->isPast()
                    ? '已截止'
                    : (((int) ($group->joined_count ?? 0)) >= $group->max_people - 2 ? '即将满员' : '报名中'),
            },
            'palette' => 'from-[#1c514b] to-[#789987]',
            'meetingInfo' => $group->meeting_info,
            'notes' => $group->notes,
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
            'highlights' => $route->highlights ?? [],
        ];
    }
}
