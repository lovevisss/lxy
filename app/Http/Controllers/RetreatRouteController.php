<?php

namespace App\Http\Controllers;

use App\Models\RetreatRoute;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class RetreatRouteController extends Controller
{
    public function index(): Response
    {
        $routes = RetreatRoute::query()
            ->where('status', 'approved')
            ->latest('approved_at')
            ->get()
            ->map(fn (RetreatRoute $route) => $this->serializeRoute($route));

        return Inertia::render('retreat/Routes', ['routeRecords' => $routes]);
    }

    public function create(): Response
    {
        return Inertia::render('retreat/CreateRoute');
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
            'notices' => ['required', 'array', 'min:1'],
            'notices.*' => ['string', 'max:500'],
            'cover_generated' => ['boolean'],
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

        $route = DB::transaction(function () use ($request, $validated): RetreatRoute {
            $days = $validated['days'];
            unset($validated['days'], $validated['cover_generated']);

            $route = RetreatRoute::create([
                ...$validated,
                'creator_id' => $request->user()->id,
                'days' => count($days),
                'cover_path' => $request->boolean('cover_generated')
                    ? '/images/retreat/changchun-changbaishan-yanji-cover.png'
                    : null,
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

        return to_route('retreat.approvals')
            ->with('success', "线路“{$route->title}”已提交二级单位审批");
    }

    public function show(Request $request, RetreatRoute $retreatRoute): Response
    {
        abort_unless(
            $retreatRoute->status === 'approved'
            || $retreatRoute->creator_id === $request->user()->id
            || $request->user()->canApproveRetreat(),
            403,
        );

        $retreatRoute->load(['itineraryDays', 'creator']);

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
        ]);
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
}
