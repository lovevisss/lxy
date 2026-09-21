<?php

namespace App\Http\Controllers;

use App\Models\RetreatRoute;
use App\Models\RetreatRouteImport;
use App\Services\RetreatRoutePdfParser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class RetreatRouteImportController extends Controller
{
    private const HEADERS = [
        '线路编码', '线路名称', '区域', '目的地', '线路简介', '天数', '最低人数', '最高人数',
        '出发城市', '返回城市', '去程交通', '返程交通', '线路亮点', '体验项目', '住宿标准',
        '用餐标准', '当地交通', '门票范围', '导游服务', '保险保障', '增值服务', '个人自理项目',
        '注意事项', '第几天', '当日标题', '当日地点', '当日交通', '上午安排', '下午安排',
        '晚上安排', '当日餐饮', '当日住宿', '当日提示',
    ];

    public function index(Request $request): Response
    {
        $this->authorizeUnionImport($request);

        $history = RetreatRouteImport::query()
            ->with('uploader')
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn (RetreatRouteImport $import) => [
                'id' => $import->id,
                'filename' => $import->original_filename,
                'uploader' => $import->uploader->name,
                'status' => $import->status,
                'totalRoutes' => $import->total_routes,
                'importedRoutes' => $import->imported_routes,
                'failedRoutes' => $import->failed_routes,
                'errors' => array_slice($import->errors ?? [], 0, 10),
                'createdAt' => $import->created_at->format('Y-m-d H:i'),
            ]);

        return Inertia::render('retreat/ImportRoutes', ['importHistory' => $history]);
    }

    public function template(Request $request): StreamedResponse
    {
        $this->authorizeUnionImport($request);

        return response()->streamDownload(function (): void {
            $output = fopen('php://output', 'wb');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, self::HEADERS);
            fputcsv($output, [
                'HZ001', '湖山疗愈·杭州两日', '华东', '浙江·杭州', '湖畔慢行与文化走读相结合的疗休养线路。',
                2, 12, 24, '杭州', '杭州', '统一大巴', '统一大巴', '湖畔慢行|人文走读', '轻徒步与文化体验',
                '舒适型酒店双人标准间', '含1次早餐、3次正餐', '全程空调旅游车', '包含行程所列首道门票',
                '专业中文导游', '旅行社责任险及旅游意外险', '每日饮用水|常用外用药品',
                '往返集合点交通|个人消费', '携带身份证|穿着防滑运动鞋', 1, '集合出发·湖畔慢行',
                '杭州', '统一大巴', '学校集合出发', '湖畔慢行', '团队交流', '中餐、晚餐', '杭州酒店', '注意防晒',
            ]);
            fputcsv($output, [
                'HZ001', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '',
                2, '人文走读·从容返程', '杭州', '统一大巴', '酒店早餐', '文化场馆走读后返校', '', '早餐、中餐', '—', '',
            ]);
            fclose($output);
        }, '工会疗休养线路导入模板.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function store(Request $request, RetreatRoutePdfParser $parser): RedirectResponse
    {
        $this->authorizeUnionImport($request);
        if ($request->hasFile('pdf')) {
            return $this->parsePdf($request, $parser);
        }

        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $file = $validated['file'];
        $storedPath = $file->store('retreat-route-imports');
        $batch = RetreatRouteImport::create([
            'uploaded_by' => $request->user()->id,
            'original_filename' => $file->getClientOriginalName(),
            'stored_path' => $storedPath,
            'status' => 'processing',
        ]);

        try {
            $groups = $this->readCsv($file->getRealPath());
        } catch (ValidationException $exception) {
            $batch->update([
                'status' => 'failed',
                'errors' => [['routeCode' => '文件', 'message' => collect($exception->errors())->flatten()->first()]],
            ]);

            throw $exception;
        }

        $errors = [];
        $imported = 0;
        foreach ($groups as $routeCode => $rows) {
            try {
                $this->importRoute($batch, $request->user()->id, $routeCode, $rows);
                $imported++;
            } catch (Throwable $exception) {
                $errors[] = [
                    'routeCode' => $routeCode,
                    'message' => $exception instanceof ValidationException
                        ? collect($exception->errors())->flatten()->first()
                        : $exception->getMessage(),
                ];
            }
        }

        $total = count($groups);
        $failed = count($errors);
        $batch->update([
            'status' => $failed === 0 ? 'success' : ($imported > 0 ? 'partial' : 'failed'),
            'total_routes' => $total,
            'imported_routes' => $imported,
            'failed_routes' => $failed,
            'errors' => $errors,
        ]);

        return back()->with('success', "导入完成：成功 {$imported} 条，失败 {$failed} 条");
    }

    public function parsePdf(Request $request, RetreatRoutePdfParser $parser): RedirectResponse
    {
        $this->authorizeUnionImport($request);
        $validated = $request->validate([
            'pdf' => ['required', 'file', 'mimes:pdf', 'max:20480'],
        ]);

        $file = $validated['pdf'];
        $storedPath = $file->store('retreat-route-pdf-imports');

        try {
            $draft = $parser->parse(Storage::disk('local')->path($storedPath));
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($storedPath);
            throw ValidationException::withMessages(['pdf' => $exception->getMessage()]);
        }

        $token = Str::random(40);
        $request->session()->put("retreat.pdf_drafts.{$token}", [
            'draft' => $draft,
            'stored_path' => $storedPath,
            'original_name' => $file->getClientOriginalName(),
            'created_at' => now()->timestamp,
        ]);

        return to_route('retreat.routes.create', ['pdf_draft' => $token])
            ->with('success', 'PDF 已解析为线路草稿，请核对后提交。');
    }

    /** @return array<string, array<int, array{row: int, data: array<string, string>}>> */
    private function readCsv(string $path): array
    {
        $content = file_get_contents($path);
        $encoding = mb_detect_encoding($content, ['UTF-8', 'GB18030', 'GBK'], true) ?: 'UTF-8';
        if ($encoding !== 'UTF-8') {
            $content = mb_convert_encoding($content, 'UTF-8', $encoding);
        }

        $stream = fopen('php://temp', 'w+b');
        fwrite($stream, $content);
        rewind($stream);
        $headers = fgetcsv($stream) ?: [];
        if (isset($headers[0])) {
            $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);
        }
        $missing = array_diff(self::HEADERS, $headers);
        if ($missing) {
            throw ValidationException::withMessages([
                'file' => '模板缺少列：'.implode('、', $missing),
            ]);
        }

        $groups = [];
        $rowNumber = 1;
        while (($values = fgetcsv($stream)) !== false) {
            $rowNumber++;
            if (count(array_filter($values, fn ($value) => trim((string) $value) !== '')) === 0) {
                continue;
            }
            $values = array_pad($values, count($headers), '');
            $data = array_combine($headers, array_slice($values, 0, count($headers)));
            $data = array_map(fn ($value) => trim((string) $value), $data);
            $routeCode = $data['线路编码'] ?? '';
            if ($routeCode === '') {
                throw ValidationException::withMessages(['file' => "第 {$rowNumber} 行缺少线路编码。"]);
            }
            $groups[$routeCode][] = ['row' => $rowNumber, 'data' => $data];
        }
        fclose($stream);

        if ($groups === []) {
            throw ValidationException::withMessages(['file' => '文件中没有可导入的线路数据。']);
        }

        return $groups;
    }

    /** @param array<int, array{row: int, data: array<string, string>}> $rows */
    private function importRoute(RetreatRouteImport $batch, int $userId, string $routeCode, array $rows): void
    {
        $masterHeaders = array_slice(self::HEADERS, 1, 22);
        $master = [];
        foreach ($masterHeaders as $header) {
            $master[$header] = collect($rows)->pluck("data.{$header}")->first(fn ($value) => $value !== '') ?? '';
        }

        $required = [
            '线路名称', '区域', '目的地', '线路简介', '天数', '最低人数', '最高人数', '出发城市', '返回城市',
            '住宿标准', '用餐标准', '当地交通', '门票范围', '导游服务', '保险保障', '个人自理项目', '注意事项',
        ];
        $missing = collect($required)->filter(fn ($field) => $master[$field] === '')->values();
        if ($missing->isNotEmpty()) {
            throw ValidationException::withMessages([
                'route' => "线路 {$routeCode} 缺少：".$missing->join('、'),
            ]);
        }

        if (RetreatRoute::where('title', $master['线路名称'])->exists()) {
            throw ValidationException::withMessages(['route' => "线路名称“{$master['线路名称']}”已存在。"]);
        }

        $days = collect($rows)->map(function ($row) use ($routeCode): array {
            $data = $row['data'];
            if (! ctype_digit($data['第几天'] ?? '') || (int) $data['第几天'] < 1) {
                throw ValidationException::withMessages(['route' => "线路 {$routeCode} 第 {$row['row']} 行的“第几天”无效。"]);
            }
            foreach (['当日标题', '当日地点'] as $field) {
                if (($data[$field] ?? '') === '') {
                    throw ValidationException::withMessages(['route' => "线路 {$routeCode} 第 {$row['row']} 行缺少{$field}。"]);
                }
            }

            return [
                'day_number' => (int) $data['第几天'],
                'title' => $data['当日标题'],
                'location' => $data['当日地点'],
                'transport' => $data['当日交通'] ?: null,
                'morning' => $data['上午安排'] ?: null,
                'afternoon' => $data['下午安排'] ?: null,
                'evening' => $data['晚上安排'] ?: null,
                'meals' => $data['当日餐饮'] ?: null,
                'stay' => $data['当日住宿'] ?: null,
                'note' => $data['当日提示'] ?: null,
            ];
        })->sortBy('day_number')->values();

        $declaredDays = (int) $master['天数'];
        if ($declaredDays < 1 || $days->count() !== $declaredDays) {
            throw ValidationException::withMessages([
                'route' => "线路 {$routeCode} 声明 {$declaredDays} 天，但实际填写 {$days->count()} 行日程。",
            ]);
        }
        if ($days->pluck('day_number')->all() !== range(1, $declaredDays)) {
            throw ValidationException::withMessages(['route' => "线路 {$routeCode} 的日程天数必须从 1 连续填写。"]);
        }

        $minPeople = (int) $master['最低人数'];
        $maxPeople = (int) $master['最高人数'];
        if ($minPeople < 1 || $maxPeople <= $minPeople) {
            throw ValidationException::withMessages(['route' => "线路 {$routeCode} 的人数范围无效。"]);
        }

        DB::transaction(function () use ($batch, $userId, $master, $days, $declaredDays, $minPeople, $maxPeople): void {
            $route = RetreatRoute::create([
                'creator_id' => $userId,
                'retreat_route_import_id' => $batch->id,
                'title' => $master['线路名称'],
                'region' => $master['区域'],
                'location' => $master['目的地'],
                'summary' => $master['线路简介'],
                'days' => $declaredDays,
                'min_people' => $minPeople,
                'max_people' => $maxPeople,
                'departure_city' => $master['出发城市'],
                'return_city' => $master['返回城市'],
                'inbound_transport' => $master['去程交通'] ?: null,
                'outbound_transport' => $master['返程交通'] ?: null,
                'highlights' => $this->splitList($master['线路亮点']),
                'experiences' => $master['体验项目'] ?: null,
                'hotel_standard' => $master['住宿标准'],
                'meal_standard' => $master['用餐标准'],
                'local_transport' => $master['当地交通'],
                'ticket_standard' => $master['门票范围'],
                'guide_service' => $master['导游服务'],
                'insurance' => $master['保险保障'],
                'value_added' => $this->splitList($master['增值服务']),
                'self_funded_items' => $this->splitList($master['个人自理项目']),
                'notices' => $this->splitList($master['注意事项']),
                'cover_path' => str_contains($master['目的地'], '长白山')
                    ? '/images/retreat/changchun-changbaishan-yanji-cover.png'
                    : null,
                'status' => 'approved',
                'current_stage' => null,
                'submitted_at' => now(),
                'approved_at' => now(),
            ]);

            foreach ($days as $day) {
                $route->itineraryDays()->create($day);
            }
        });
    }

    /** @return array<int, string> */
    private function splitList(string $value): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/[|｜]/u', $value) ?: [])));
    }

    private function authorizeUnionImport(Request $request): void
    {
        abort_unless(in_array($request->user()->role, ['admin', 'union_approver'], true), 403);
    }
}
