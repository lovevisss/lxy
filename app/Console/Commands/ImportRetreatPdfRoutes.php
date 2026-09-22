<?php

namespace App\Console\Commands;

use App\Models\RetreatRoute;
use App\Models\RetreatRouteImport;
use App\Models\User;
use App\Services\RetreatBatchPdfRouteParser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ImportRetreatPdfRoutes extends Command
{
    protected $signature = 'retreat:import-pdf-routes {directory : PDF 所在目录} {--admin=admin@lxy.edu.cn : 工会导入账号邮箱} {--dry-run : 仅解析校验，不写入数据库}';

    protected $description = '批量导入康远疗休养 PDF 方案，保留原 PDF 与逐日行程';

    /** @var array<string, array{region: string, location: string, days: int}> */
    private const ROUTES = [
        '安徽宣城、绩溪' => ['region' => '华东', 'location' => '安徽·宣城 / 绩溪', 'days' => 5],
        '福建平潭泉州' => ['region' => '华东', 'location' => '福建·平潭 / 泉州', 'days' => 5],
        '杭州千岛湖建德三日' => ['region' => '华东', 'location' => '浙江·千岛湖 / 建德', 'days' => 3],
        '杭州千岛湖建德五日' => ['region' => '华东', 'location' => '浙江·千岛湖 / 建德', 'days' => 5],
        '江西庐山' => ['region' => '华东', 'location' => '江西·九江 / 庐山 / 景德镇', 'days' => 5],
        '临安' => ['region' => '华东', 'location' => '浙江·临安 / 天目山 / 湍口', 'days' => 4],
        '温州洞头' => ['region' => '华东', 'location' => '浙江·温州 / 洞头', 'days' => 3],
        '温州泰顺、文成' => ['region' => '华东', 'location' => '浙江·温州 / 泰顺 / 文成', 'days' => 5],
        '温州雁荡山' => ['region' => '华东', 'location' => '浙江·温州 / 雁荡山', 'days' => 4],
        '西宁、青海湖、德令哈、茶卡盐湖' => ['region' => '西北', 'location' => '青海·西宁 / 茶卡盐湖 / 德令哈 / 青海湖', 'days' => 7],
        '新疆阿克苏、温宿、库车' => ['region' => '西北', 'location' => '新疆·阿克苏 / 温宿 / 库车', 'days' => 5],
        '长春长白山延吉' => ['region' => '东北', 'location' => '吉林·长春 / 长白山 / 延吉', 'days' => 5],
        '重庆' => ['region' => '西南', 'location' => '重庆·主城 / 涪陵', 'days' => 5],
    ];

    public function handle(RetreatBatchPdfRouteParser $parser): int
    {
        $directory = (string) $this->argument('directory');
        if (! is_dir($directory) || ! is_readable($directory)) {
            $this->error('目录不可读取：'.$directory);

            return self::FAILURE;
        }

        $files = array_values(array_filter(scandir($directory) ?: [], fn (string $name) => preg_match('/\.pdf$/iu', $name) === 1));
        sort($files, SORT_NATURAL);
        if ($files === []) {
            $this->error('目录中没有 PDF 文件。');

            return self::FAILURE;
        }

        $prepared = [];
        try {
            foreach ($files as $filename) {
                $cleanName = preg_replace('/[\x00-\x1F\x7F]/u', '', $filename) ?? $filename;
                $spec = null;
                foreach (self::ROUTES as $prefix => $candidate) {
                    if (str_starts_with($cleanName, $prefix)) {
                        $spec = $candidate;
                        break;
                    }
                }
                if ($spec === null) {
                    throw new RuntimeException('未配置的线路文件：'.$cleanName);
                }

                $path = $directory.DIRECTORY_SEPARATOR.$filename;
                $draft = $parser->parse($path, $spec['days']);
                $storedPath = 'retreat-route-pdf-imports/'.hash_file('sha256', $path).'.pdf';
                $exists = RetreatRoute::query()->where('attachment_path', $storedPath)->exists()
                    || RetreatRoute::query()->where('title', $draft['title'])->exists();
                $prepared[] = compact('path', 'cleanName', 'spec', 'draft', 'storedPath', 'exists');
                $this->line(($exists ? '跳过已存在 ' : '可导入 ').$draft['title'].'（'.$spec['days'].'天）');
            }
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $new = array_values(array_filter($prepared, fn (array $item) => ! $item['exists']));
        $this->info('校验完成：'.count($prepared).' 份 PDF，待导入 '.count($new).' 条。');
        if ($this->option('dry-run') || $new === []) {
            return self::SUCCESS;
        }

        $admin = User::query()->where('email', $this->option('admin'))->whereIn('role', ['admin', 'union_approver'])->first();
        if (! $admin) {
            $this->error('未找到可执行工会导入的账号。');

            return self::FAILURE;
        }

        try {
            foreach ($new as $item) {
                if (! Storage::disk('local')->exists($item['storedPath'])) {
                    $stream = fopen($item['path'], 'rb');
                    if (! $stream) {
                        throw new RuntimeException('无法打开文件：'.$item['cleanName']);
                    }
                    try {
                        if (! Storage::disk('local')->put($item['storedPath'], $stream)) {
                            throw new RuntimeException('无法保存附件：'.$item['cleanName']);
                        }
                    } finally {
                        fclose($stream);
                    }
                }
            }

            DB::transaction(function () use ($new, $admin): void {
                $batch = RetreatRouteImport::create([
                    'uploaded_by' => $admin->id,
                    'original_filename' => '康远标段一 PDF 线路（'.count($new).'份）',
                    'status' => 'processing',
                    'total_routes' => count($new),
                ]);

                foreach ($new as $item) {
                    $draft = $item['draft'];
                    $spec = $item['spec'];
                    $days = $draft['days'];
                    unset($draft['days']);
                    $route = RetreatRoute::create([
                        ...$draft,
                        'creator_id' => $admin->id,
                        'retreat_route_import_id' => $batch->id,
                        'provider_name' => '康远国际旅行社',
                        'region' => $spec['region'],
                        'location' => $spec['location'],
                        'summary' => '康远国际旅行社提供的'.$spec['days'].'日疗休养方案，途经'.$spec['location'].'。具体安排与服务承诺请查看逐日行程及原方案 PDF。',
                        'days' => $spec['days'],
                        'min_people' => 20,
                        'max_people' => 25,
                        'departure_city' => '杭州',
                        'return_city' => '杭州',
                        'inbound_transport' => str_contains($draft['title'], '双飞') ? '飞机（具体航班见原 PDF）' : null,
                        'outbound_transport' => str_contains($draft['title'], '双飞') ? '飞机（具体航班见原 PDF）' : null,
                        'experiences' => implode('、', array_slice($draft['highlights'], 0, 5)),
                        'value_added' => [],
                        'self_funded_items' => ['个人消费及原方案未列明的项目；往返大交通是否包含请以原 PDF 和工会通知为准'],
                        'notices' => [
                            '工会经费按每人每天 500 元执行，超出或自理部分以工会通知为准。',
                            '系统暂按 20–25 人显示组团范围，实际人数以工会确定为准。',
                            '实际出行时间、交通班次与酒店以成团通知为准。',
                        ],
                        'cover_path' => str_contains($draft['title'], '长白山') ? '/images/retreat/changchun-changbaishan-yanji-cover.png' : null,
                        'attachment_path' => $item['storedPath'],
                        'attachment_name' => $item['cleanName'],
                        'status' => 'approved',
                        'submitted_at' => now(),
                        'approved_at' => now(),
                    ]);

                    foreach ($days as $day) {
                        $route->itineraryDays()->create([...$day, 'location' => $spec['location']]);
                    }
                }

                $batch->update(['status' => 'success', 'imported_routes' => count($new)]);
            });
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('已导入 '.count($new).' 条线路，原 PDF 已保存为可下载附件。');

        return self::SUCCESS;
    }
}
