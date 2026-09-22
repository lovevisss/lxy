<?php

namespace App\Http\Controllers;

use App\Models\RetreatGroup;
use App\Models\RetreatGroupReview;
use App\Models\RetreatRoute;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RetreatRouteController extends Controller
{
    public function index(Request $request): Response
    {
        $routes = RetreatRoute::query()
            ->with('reviews')
            ->where('status', 'approved')
            ->latest('approved_at')
            ->get()
            ->map(fn (RetreatRoute $route) => $this->serializeRoute($route));

        return Inertia::render('retreat/Routes', [
            'routeRecords' => $routes,
            'canImport' => in_array($request->user()->role, ['admin', 'union_approver'], true),
        ]);
    }

    public function create(Request $request): Response
    {
        $token = trim((string) $request->query('pdf_draft'));
        $pdfDraft = $token !== '' ? $request->session()->get("retreat.pdf_drafts.{$token}") : null;

        return Inertia::render('retreat/CreateRoute', [
            'pdfDraft' => $pdfDraft['draft'] ?? null,
            'pdfDraftToken' => $pdfDraft ? $token : null,
            'pdfSourceName' => $pdfDraft['original_name'] ?? null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:100'],
            'region' => ['nullable', 'string', 'max:30'],
            'location' => ['required', 'string', 'max:150'],
            'summary' => ['required', 'string', 'max:1000'],
            'min_people' => ['required', 'integer', 'min:1'],
            'max_people' => ['required', 'integer', 'gt:min_people', 'max:500'],
            'departure_city' => ['required', 'string', 'max:60'],
            'return_city' => ['required', 'string', 'max:60'],
            'inbound_transport' => ['nullable', 'string', 'max:255'],
            'outbound_transport' => ['nullable', 'string', 'max:255'],
            'highlights' => ['nullable', 'array'],
            'highlights.*' => ['string', 'max:150'],
            'experiences' => ['nullable', 'string', 'max:2000'],
            'hotel_standard' => ['required', 'string', 'max:3000'],
            'meal_standard' => ['required', 'string', 'max:3000'],
            'local_transport' => ['required', 'string', 'max:2000'],
            'ticket_standard' => ['required', 'string', 'max:2000'],
            'guide_service' => ['required', 'string', 'max:2000'],
            'insurance' => ['required', 'string', 'max:2000'],
            'value_added' => ['nullable', 'array'],
            'value_added.*' => ['string', 'max:500'],
            'self_funded_items' => ['required', 'array', 'min:1'],
            'self_funded_items.*' => ['string', 'max:500'],
            'notices' => ['required', 'array', 'min:1'],
            'notices.*' => ['string', 'max:500'],
            'cover_generated' => ['boolean'],
            'pdf_draft_token' => ['nullable', 'string', 'size:40'],
            'days' => ['required', 'array', 'min:1'],
            'days.*.title' => ['required', 'string', 'max:150'],
            'days.*.location' => ['required', 'string', 'max:150'],
            'days.*.transport' => ['nullable', 'string', 'max:255'],
            'days.*.morning' => ['nullable', 'string', 'max:2000'],
            'days.*.afternoon' => ['nullable', 'string', 'max:2000'],
            'days.*.evening' => ['nullable', 'string', 'max:2000'],
            'days.*.plan' => ['nullable', 'string', 'max:3000'],
            'days.*.meals' => ['nullable', 'string', 'max:255'],
            'days.*.stay' => ['nullable', 'string', 'max:255'],
            'days.*.note' => ['nullable', 'string', 'max:1000'],
        ]);

        $pdfDraftToken = $validated['pdf_draft_token'] ?? null;
        $pdfDraft = $pdfDraftToken
            ? $request->session()->get("retreat.pdf_drafts.{$pdfDraftToken}")
            : null;
        if ($pdfDraftToken && ! $pdfDraft) {
            throw ValidationException::withMessages([
                'pdf_draft_token' => 'PDF 草稿已过期，请重新上传解析。',
            ]);
        }

        $route = DB::transaction(function () use ($request, $validated, $pdfDraft): RetreatRoute {
            $days = $validated['days'];
            unset($validated['days'], $validated['cover_generated'], $validated['pdf_draft_token']);

            $route = RetreatRoute::create([
                ...$validated,
                'creator_id' => $request->user()->id,
                'days' => count($days),
                'cover_path' => $request->boolean('cover_generated')
                    ? '/images/retreat/changchun-changbaishan-yanji-cover.png'
                    : null,
                'attachment_path' => $pdfDraft['stored_path'] ?? null,
                'attachment_name' => $pdfDraft['original_name'] ?? null,
                'status' => 'pending_department',
                'current_stage' => 'department',
                'submitted_at' => now(),
            ]);

            foreach (array_values($days) as $index => $day) {
                $route->itineraryDays()->create([
                    ...$day,
                    'day_number' => $index + 1,
                ]);
            }

            return $route;
        });

        if ($pdfDraftToken) {
            $request->session()->forget("retreat.pdf_drafts.{$pdfDraftToken}");
        }

        return to_route('retreat.routes.show', $route)
            ->with('success', "线路“{$route->title}”已提交二级单位审批");
    }

    public function downloadAttachment(Request $request, RetreatRoute $retreatRoute)
    {
        abort_unless(
            $retreatRoute->status === 'approved'
            || $retreatRoute->creator_id === $request->user()->id
            || $request->user()->isRetreatAdmin()
            || $request->user()->canApproveRetreat(),
            403,
        );
        abort_unless($retreatRoute->attachment_path, 404);
        abort_unless(Storage::disk('local')->exists($retreatRoute->attachment_path), 404);

        return Storage::disk('local')->download(
            $retreatRoute->attachment_path,
            $retreatRoute->attachment_name ?: '线路方案.pdf',
        );
    }

    public function show(Request $request, RetreatRoute $retreatRoute): Response
    {
        abort_unless(
            $retreatRoute->status === 'approved'
            || $retreatRoute->creator_id === $request->user()->id
            || $request->user()->isRetreatAdmin()
            || $request->user()->canApproveRetreat(),
            403,
        );

        $retreatRoute->load(['itineraryDays', 'creator', 'reviews.user']);

        $reviewableGroup = RetreatGroup::query()
            ->where('retreat_route_id', $retreatRoute->id)
            ->whereDate('return_date', '<', today())
            ->whereHas('applications', fn ($query) => $query
                ->where('user_id', $request->user()->id)
                ->where('status', 'approved'))
            ->latest('return_date')
            ->first();

        $recommendedGroups = RetreatGroup::query()
            ->with(['route.reviews', 'leader'])
            ->withSum(['applications as joined_count' => fn ($query) => $query->where('status', 'approved')], 'member_count')
            ->where('status', 'open')
            ->whereDate('application_deadline', '>=', today())
            ->whereDate('departure_date', '>=', today())
            ->whereHas('route', fn ($query) => $query
                ->where('status', 'approved')
                ->where(function ($routeQuery) use ($retreatRoute): void {
                    $routeQuery->where('id', $retreatRoute->id)
                        ->orWhere('region', $retreatRoute->region)
                        ->orWhereBetween('days', [max(1, $retreatRoute->days - 1), $retreatRoute->days + 1]);
                }))
            ->orderByRaw('CASE WHEN retreat_route_id = ? THEN 0 ELSE 1 END', [$retreatRoute->id])
            ->oldest('departure_date')
            ->limit(4)
            ->get()
            ->map(fn (RetreatGroup $group) => $this->serializeRecommendedGroup($group, $retreatRoute->id));

        return Inertia::render('retreat/RouteDetail', [
            'routeId' => $retreatRoute->id,
            'routeRecord' => $this->serializeRoute($retreatRoute),
            'itineraryRecord' => $retreatRoute->itineraryDays->map(fn ($day) => [
                'day' => $day->day_number,
                'title' => $day->title,
                'location' => $day->location,
                'period' => $day->transport ?: '全天',
                'plan' => collect([$day->morning, $day->afternoon, $day->evening, $day->plan])->filter()->join('；'),
                'stay' => $day->stay ?: '待定',
                'note' => collect([$day->meals, $day->note])->filter()->join('；'),
            ]),
            'reviewableGroup' => $reviewableGroup ? [
                'id' => $reviewableGroup->id,
                'title' => $reviewableGroup->title,
                'returnDate' => $reviewableGroup->return_date->toDateString(),
            ] : null,
            'currentReview' => $retreatRoute->reviews
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
            'reviewSummary' => $this->reviewSummary($retreatRoute),
            'reviewRecords' => $retreatRoute->reviews
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
            'recommendedGroups' => $recommendedGroups,
        ]);
    }

    public function storeReview(Request $request, RetreatRoute $retreatRoute): RedirectResponse
    {
        $validated = $request->validate([
            'retreat_group_id' => ['required', 'integer', 'exists:retreat_groups,id'],
            'route_score' => ['required', 'integer', 'between:1,5'],
            'meal_score' => ['required', 'integer', 'between:1,5'],
            'attraction_score' => ['required', 'integer', 'between:1,5'],
            'accommodation_score' => ['required', 'integer', 'between:1,5'],
            'service_score' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $group = RetreatGroup::query()
            ->where('id', $validated['retreat_group_id'])
            ->where('retreat_route_id', $retreatRoute->id)
            ->first();

        if (! $group) {
            throw ValidationException::withMessages([
                'retreat_group_id' => '所选参团记录不属于当前线路。',
            ]);
        }

        abort_unless($group->return_date->lt(today()), 422, '行程结束后才能提交评价。');
        abort_unless(
            $group->applications()
                ->where('user_id', $request->user()->id)
                ->where('status', 'approved')
                ->exists(),
            403,
        );

        unset($validated['retreat_group_id']);
        RetreatGroupReview::updateOrCreate(
            [
                'retreat_route_id' => $retreatRoute->id,
                'user_id' => $request->user()->id,
            ],
            [
                ...$validated,
                'retreat_group_id' => $group->id,
            ],
        );

        return back()->with('success', '感谢您的评价，本次反馈已汇总到线路口碑');
    }

    private function serializeRoute(RetreatRoute $route): array
    {
        $reviews = $route->relationLoaded('reviews') ? $route->reviews : collect();

        return [
            'id' => $route->id,
            'title' => $route->title,
            'providerName' => $route->provider_name,
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
            'attachmentName' => $route->attachment_name,
            'attachmentUrl' => $route->attachment_path
                ? route('retreat.routes.attachment', $route)
                : null,
            'importedByUnion' => $route->retreat_route_import_id !== null,
            'rating' => $reviews->isEmpty()
                ? null
                : round($reviews->average(fn (RetreatGroupReview $review) => $review->overallScore()), 1),
            'reviewCount' => $reviews->count(),
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
            'service' => [
                'inboundTransport' => $route->inbound_transport ?: '待团长确认',
                'outboundTransport' => $route->outbound_transport ?: '待团长确认',
                'hotels' => $route->hotel_standard,
                'meals' => $route->meal_standard,
                'localTransport' => $route->local_transport,
                'tickets' => $route->ticket_standard,
                'guide' => $route->guide_service,
                'insurance' => $route->insurance,
                'extras' => $route->value_added ?? [],
                'notices' => $route->notices ?? [],
            ],
            'status' => $route->status,
            'creator' => $route->relationLoaded('creator') ? $route->creator?->name : null,
        ];
    }

    private function reviewSummary(RetreatRoute $route): array
    {
        $reviews = $route->reviews;

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

    private function serializeRecommendedGroup(RetreatGroup $group, int $currentRouteId): array
    {
        $routeReviews = $group->route->reviews;

        return [
            'id' => $group->id,
            'title' => $group->title,
            'routeTitle' => $group->route->title,
            'location' => $group->route->location,
            'days' => $group->route->days,
            'leader' => $group->leader->name,
            'date' => $group->departure_date->format('m月d日').'—'.$group->return_date->format('m月d日'),
            'joined' => (int) ($group->joined_count ?? 0),
            'capacity' => $group->max_people,
            'remaining' => max(0, $group->max_people - (int) ($group->joined_count ?? 0)),
            'sameRoute' => $group->retreat_route_id === $currentRouteId,
            'routeRating' => $routeReviews->isEmpty()
                ? null
                : round($routeReviews->average(fn (RetreatGroupReview $review) => $review->overallScore()), 1),
            'reviewCount' => $routeReviews->count(),
        ];
    }
}
