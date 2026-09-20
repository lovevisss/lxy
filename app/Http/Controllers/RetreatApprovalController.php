<?php

namespace App\Http\Controllers;

use App\Models\RetreatRoute;
use App\Models\RetreatRouteApproval;
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

        if ($request->user()->role === 'department_approver') {
            $query->where('status', 'pending_department');
        } elseif ($request->user()->role === 'union_approver') {
            $query->where('status', 'pending_union');
        }

        $items = $query
            ->get()
            ->map(fn (RetreatRoute $route) => [
                'id' => $route->id,
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

        $history = RetreatRouteApproval::query()
            ->with(['route.creator', 'route.itineraryDays'])
            ->where('approver_id', $request->user()->id)
            ->latest()
            ->get()
            ->map(function (RetreatRouteApproval $approval): array {
                $route = $approval->route;

                return [
                    'id' => $approval->id,
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

        return Inertia::render('retreat/Approvals', [
            'approvalRecords' => $items,
            'approvalHistory' => $history,
        ]);
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
        abort_unless($request->user()->canApproveRetreat(), 403);
    }

    private function authorizeRouteStage(Request $request, RetreatRoute $route): void
    {
        $this->authorizeApprover($request);
        abort_unless(in_array($route->status, ['pending_department', 'pending_union'], true), 422);

        abort_unless(
            $request->user()->isRetreatAdmin()
            || ($request->user()->role === 'department_approver' && $route->status === 'pending_department')
            || ($request->user()->role === 'union_approver' && $route->status === 'pending_union'),
            403,
        );
    }
}
