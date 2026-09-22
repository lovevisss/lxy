<?php

namespace App\Http\Controllers;

use App\Models\RetreatGroupApprovalEvent;
use App\Models\RetreatGroupApprovalNode;
use App\Models\RetreatRoute;
use App\Models\RetreatRouteApproval;
use App\Services\RetreatGroupApprovalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class RetreatApprovalController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorizeApprover($request);

        $query = RetreatRoute::query()
            ->with(['creator', 'itineraryDays'])
            ->whereIn('status', ['pending_department', 'pending_union'])
            ->oldest('submitted_at');

        if (! $request->user()->canApproveRetreat()) {
            $query->whereRaw('1 = 0');
        } elseif ($request->user()->role === 'department_approver') {
            $query->where('status', 'pending_department');
        } elseif ($request->user()->role === 'union_approver') {
            $query->where('status', 'pending_union');
        }

        $items = $query
            ->get()
            ->map(fn (RetreatRoute $route) => [
                'id' => $route->id,
                'key' => 'route-'.$route->id,
                'kind' => 'route',
                'type' => $route->status === 'pending_department' ? '线路初审' : '校工会终审',
                'title' => $route->title,
                'applicant' => $route->creator->name,
                'department' => $route->creator->department ?: '未设置单位',
                'time' => $route->submitted_at?->diffForHumans() ?? '刚刚',
                'stay' => $route->updated_at->diffForHumans(short: true),
                'status' => '待处理',
                'days' => $route->days,
                'people' => "{$route->min_people}—{$route->max_people} 人",
                'location' => $route->location,
                'summary' => $route->summary,
                'dailySubsidy' => 500,
                'estimatedSubsidy' => $route->days * 500,
                'selfFundedItems' => $route->self_funded_items ?? [
                    '往返大交通费用（如机票、高铁票）',
                    '个人消费及方案未列明项目',
                ],
                'stage' => $route->current_stage,
                'itinerary' => $route->itineraryDays->map(fn ($day) => [
                    'day' => $day->day_number,
                    'title' => $day->title,
                ]),
            ]);

        $routeHistory = RetreatRouteApproval::query()
            ->with(['route.creator', 'route.itineraryDays'])
            ->where('approver_id', $request->user()->id)
            ->latest()
            ->get()
            ->map(function (RetreatRouteApproval $approval): array {
                $route = $approval->route;

                return [
                    'id' => $approval->id,
                    'key' => 'route-history-'.$approval->id,
                    'kind' => 'route',
                    'routeId' => $route->id,
                    'type' => $approval->stage === 'department' ? '线路初审' : '校工会终审',
                    'title' => $route->title,
                    'applicant' => $route->creator->name,
                    'department' => $route->creator->department ?: '未设置单位',
                    'time' => $approval->created_at->diffForHumans(),
                    'stay' => $approval->created_at->diffForHumans(short: true),
                    'status' => $approval->action === 'approved' ? '已通过' : '已退回',
                    'days' => $route->days,
                    'people' => "{$route->min_people}—{$route->max_people} 人",
                    'location' => $route->location,
                    'summary' => $route->summary,
                    'dailySubsidy' => 500,
                    'estimatedSubsidy' => $route->days * 500,
                    'selfFundedItems' => $route->self_funded_items ?? [
                        '往返大交通费用（如机票、高铁票）',
                        '个人消费及方案未列明项目',
                    ],
                    'stage' => $approval->stage,
                    'decision' => $approval->action,
                    'comment' => $approval->comment,
                    'itinerary' => $route->itineraryDays->map(fn ($day) => [
                        'day' => $day->day_number,
                        'title' => $day->title,
                    ]),
                ];
            });

        $groupQuery = RetreatGroupApprovalNode::query()
            ->with(['group.route', 'group.leader', 'group.approvalNodes'])
            ->where('active', true)
            ->where('status', 'pending')
            ->whereHas('group', fn ($query) => $query->whereIn('approval_status', ['pending_departments', 'pending_final']));
        if ($request->user()->isGroupFinalReviewer() && $request->user()->isGroupDepartmentReviewer()) {
            $groupQuery->where(function ($query) use ($request): void {
                $query->where('stage', 'final')
                    ->orWhere(fn ($department) => $department->where('stage', 'department')
                        ->where('department', $request->user()->department));
            });
        } elseif ($request->user()->isGroupFinalReviewer()) {
            $groupQuery->where('stage', 'final');
        } elseif ($request->user()->isGroupDepartmentReviewer()) {
            $groupQuery->where('stage', 'department')->where('department', $request->user()->department);
        } else {
            $groupQuery->whereRaw('1 = 0');
        }

        $groupItems = $groupQuery->oldest()->get()->map(fn (RetreatGroupApprovalNode $node) => $this->serializeGroupNode($node));
        $groupHistory = RetreatGroupApprovalEvent::query()
            ->with(['group.route', 'group.leader', 'node', 'group.approvalNodes'])
            ->where('actor_id', $request->user()->id)
            ->whereIn('event', ['approved', 'rejected'])
            ->latest()->get()
            ->map(fn (RetreatGroupApprovalEvent $event) => $this->serializeGroupNode($event->node, $event));

        return Inertia::render('retreat/Approvals', [
            'approvalRecords' => $items->concat($groupItems)->values(),
            'approvalHistory' => $routeHistory->concat($groupHistory)->values(),
        ]);
    }

    public function reviewGroup(
        Request $request,
        RetreatGroupApprovalNode $approvalNode,
        RetreatGroupApprovalService $approvalService,
    ): RedirectResponse {
        $validated = $request->validate([
            'action' => ['required', 'in:approved,rejected'],
            'comment' => ['nullable', 'required_if:action,rejected', 'string', 'max:1000'],
        ]);
        $queued = $approvalService->review(
            $approvalNode,
            $request->user(),
            $validated['action'],
            $validated['comment'] ?? null,
        );

        $message = $validated['action'] === 'rejected'
            ? '团队已退回团长修改'
            : ($approvalNode->stage === 'final'
                ? "成团终审已通过，已生成 {$queued} 条最终确认短信任务"
                : '本单位会签已通过');

        return back()->with('success', $message);
    }

    public function approve(Request $request, RetreatRoute $retreatRoute): RedirectResponse
    {
        $this->authorizeRouteStage($request, $retreatRoute);
        $validated = $request->validate(['comment' => ['nullable', 'string', 'max:1000']]);

        abort_unless(in_array($retreatRoute->status, ['pending_department', 'pending_union'], true), 422);

        DB::transaction(function () use ($request, $retreatRoute, $validated): void {
            $stage = $retreatRoute->current_stage;
            $retreatRoute->approvals()->create([
                'approver_id' => $request->user()->id,
                'stage' => $stage,
                'action' => 'approved',
                'comment' => $validated['comment'] ?? null,
            ]);

            if ($retreatRoute->status === 'pending_department') {
                $retreatRoute->update([
                    'status' => 'pending_union',
                    'current_stage' => 'union',
                ]);
            } else {
                $retreatRoute->update([
                    'status' => 'approved',
                    'current_stage' => null,
                    'approved_at' => now(),
                    'rejection_reason' => null,
                ]);
            }
        });

        return back()->with('success', $retreatRoute->status === 'approved'
            ? '校工会终审通过，线路已发布'
            : '二级单位初审通过，已流转至校工会');
    }

    public function reject(Request $request, RetreatRoute $retreatRoute): RedirectResponse
    {
        $this->authorizeRouteStage($request, $retreatRoute);
        $validated = $request->validate(['comment' => ['required', 'string', 'max:1000']]);

        DB::transaction(function () use ($request, $retreatRoute, $validated): void {
            $retreatRoute->approvals()->create([
                'approver_id' => $request->user()->id,
                'stage' => $retreatRoute->current_stage,
                'action' => 'rejected',
                'comment' => $validated['comment'],
            ]);
            $retreatRoute->update([
                'status' => 'rejected',
                'current_stage' => null,
                'rejection_reason' => $validated['comment'],
            ]);
        });

        return back()->with('success', '线路已退回发起人修改');
    }

    private function authorizeApprover(Request $request): void
    {
        abort_unless($request->user()->canApproveRetreat() || $request->user()->canApproveGroup(), 403);
    }

    private function authorizeRouteStage(Request $request, RetreatRoute $route): void
    {
        abort_unless($request->user()->canApproveRetreat(), 403);
        abort_unless(in_array($route->status, ['pending_department', 'pending_union'], true), 422);

        abort_unless(
            ($request->user()->role === 'department_approver' && $route->status === 'pending_department')
            || ($request->user()->role === 'union_approver' && $route->status === 'pending_union'),
            403,
        );
    }

    private function serializeGroupNode(
        ?RetreatGroupApprovalNode $node,
        ?RetreatGroupApprovalEvent $event = null,
    ): array {
        abort_unless($node, 404);
        $group = $node->group;
        $nodes = $group->approvalNodes->where('active', true)->where('stage', 'department');

        return [
            'id' => $node->id,
            'key' => 'group-'.$node->id.($event ? '-event-'.$event->id : ''),
            'kind' => 'group',
            'type' => $node->stage === 'final' ? '成团终审' : '分院会签',
            'title' => $group->title,
            'applicant' => $group->leader->name,
            'department' => $node->department ?: '全校汇总',
            'time' => ($event?->created_at ?? $node->created_at)->diffForHumans(),
            'stay' => ($event?->created_at ?? $node->created_at)->diffForHumans(short: true),
            'status' => $event ? ($event->event === 'approved' ? '已通过' : '已退回') : '待处理',
            'stage' => $node->stage === 'final' ? 'group_final' : 'group_department',
            'decision' => $event?->event,
            'comment' => $event?->comment,
            'groupId' => $group->id,
            'routeTitle' => $group->route->title,
            'date' => $group->departure_date->format('Y-m-d').' — '.$group->return_date->format('Y-m-d'),
            'members' => $event?->metadata['members'] ?? $node->member_snapshot ?? [],
            'departmentProgress' => [
                'approved' => $nodes->where('status', 'approved')->count(),
                'total' => $nodes->count(),
                'items' => $nodes->values()->map(fn (RetreatGroupApprovalNode $departmentNode) => [
                    'department' => $departmentNode->department,
                    'status' => $departmentNode->status,
                    'reviewer' => $departmentNode->reviewer?->name,
                ]),
            ],
        ];
    }
}
