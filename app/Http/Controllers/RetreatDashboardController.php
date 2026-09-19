<?php

namespace App\Http\Controllers;

use App\Models\RetreatGroup;
use App\Models\RetreatGroupApplication;
use App\Models\RetreatRoute;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RetreatDashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $approvedRoutes = RetreatRoute::where('status', 'approved');
        $openGroups = RetreatGroup::where('status', 'open')
            ->whereDate('application_deadline', '>=', today());

        $pendingRoutes = RetreatRoute::query()
            ->when($user->role === 'department_approver', fn ($query) => $query->where('status', 'pending_department'))
            ->when($user->role === 'union_approver', fn ($query) => $query->where('status', 'pending_union'))
            ->when($user->isRetreatAdmin(), fn ($query) => $query->whereIn('status', ['pending_department', 'pending_union']))
            ->when(! $user->canApproveRetreat(), fn ($query) => $query->whereRaw('1 = 0'));

        $pendingApplications = RetreatGroupApplication::query()
            ->where('status', 'pending')
            ->whereHas('group', fn ($query) => $query->when(
                ! $user->isRetreatAdmin(),
                fn ($groupQuery) => $groupQuery->where('leader_id', $user->id),
            ));

        $featured = $approvedRoutes->clone()->latest('approved_at')->first();
        $nextGroup = RetreatGroup::query()
            ->with(['route', 'leader'])
            ->withSum(['applications as joined_count' => fn ($query) => $query->where('status', 'approved')], 'member_count')
            ->whereDate('departure_date', '>=', today())
            ->where(function ($query) use ($user): void {
                $query->where('leader_id', $user->id)
                    ->orWhereHas('applications', fn ($applicationQuery) => $applicationQuery
                        ->where('user_id', $user->id)
                        ->where('status', 'approved'));
            })
            ->oldest('departure_date')
            ->first();

        $todos = $pendingRoutes->clone()
            ->with('creator')
            ->oldest('submitted_at')
            ->limit(3)
            ->get()
            ->map(fn (RetreatRoute $route) => [
                'id' => "route-{$route->id}",
                'title' => $route->title.' · '.($route->status === 'pending_union' ? '终审' : '初审'),
                'description' => $route->creator->name.' · '.($route->creator->department ?: '未设置单位'),
                'href' => '/approvals',
                'kind' => 'approval',
            ]);

        $applicationTodos = $pendingApplications->clone()
            ->with(['group', 'user'])
            ->oldest()
            ->limit(max(0, 3 - $todos->count()))
            ->get()
            ->map(fn (RetreatGroupApplication $application) => [
                'id' => "application-{$application->id}",
                'title' => $application->group->title.' · 报名待审核',
                'description' => $application->user->name.'申请 '.$application->member_count.' 人参团',
                'href' => "/groups/{$application->retreat_group_id}",
                'kind' => 'application',
            ]);

        return Inertia::render('Dashboard', [
            'dashboard' => [
                'approvedRouteCount' => $approvedRoutes->count(),
                'openGroupCount' => $openGroups->count(),
                'todoCount' => $pendingRoutes->count() + $pendingApplications->count(),
                'featuredRoute' => $featured ? [
                    'id' => $featured->id,
                    'title' => $featured->title,
                    'location' => $featured->location,
                    'region' => $featured->region ?: '其他',
                    'days' => $featured->days,
                    'people' => "{$featured->min_people}—{$featured->max_people} 人",
                    'summary' => $featured->summary,
                    'tags' => array_slice($featured->highlights ?? ['疗休养'], 0, 3),
                    'palette' => 'from-[#173f4a] via-[#32667a] to-[#91ad9d]',
                    'accent' => '#f2d49b',
                    'updatedAt' => $featured->updated_at->format('m-d'),
                    'favorite' => false,
                    'cover' => $featured->cover_path,
                ] : null,
                'nextGroup' => $nextGroup ? [
                    'id' => $nextGroup->id,
                    'title' => $nextGroup->title,
                    'route' => $nextGroup->route->title,
                    'leader' => $nextGroup->leader->name,
                    'department' => $nextGroup->leader->department ?: '未设置单位',
                    'date' => $nextGroup->departure_date->format('m月d日').'—'.$nextGroup->return_date->format('m月d日'),
                    'deadline' => '报名截止 '.$nextGroup->application_deadline->format('m月d日'),
                    'joined' => (int) ($nextGroup->joined_count ?? 0),
                    'capacity' => $nextGroup->max_people,
                    'min' => $nextGroup->min_people,
                    'status' => '报名中',
                    'palette' => 'from-[#1c514b] to-[#789987]',
                ] : null,
                'todos' => $todos->concat($applicationTodos)->values(),
            ],
        ]);
    }
}
