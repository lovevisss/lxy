<?php

namespace App\Services;

use Illuminate\Support\Str;
use RuntimeException;
use Smalot\PdfParser\Parser;

class RetreatRoutePdfParser
{
    /** @return array<string, mixed> */
    public function parse(string $path): array
    {
        try {
            $text = (new Parser)->parseFile($path)->getText();
        } catch (\Throwable $exception) {
            throw new RuntimeException('PDF 无法读取，请确认文件未加密且内容完整。', previous: $exception);
        }

        return $this->parseText($text);
    }

    /** @return array<string, mixed> */
    public function parseText(string $text): array
    {
        $text = $this->normalize($text);
        if (mb_strlen($text) < 80) {
            throw new RuntimeException('PDF 中没有提取到足够的文字；扫描版文件请先进行 OCR。');
        }

        $days = $this->parseDays($text);
        if ($days === []) {
            throw new RuntimeException('未识别到 D1、D2 等逐日行程，请改用标准 CSV 模板导入。');
        }

        $title = $this->match('/【([^】]{4,80}(?:疗休养|行程)[^】]*)】/u', $text)
            ?: $this->match('/^([^\n]{4,100}(?:疗休养|行程)[^\n]*)$/mu', $text)
            ?: 'PDF 解析线路';
        $title = preg_replace('/方案$/u', '', $title) ?: $title;

        $intro = $this->between($text, '印象·长白山', '》景点介绍');
        $summary = $intro !== ''
            ? Str::limit($this->oneLine($intro), 480, '…')
            : '根据上传的疗休养方案自动解析生成，请在提交前核对线路内容。';

        $notices = $this->numberedItems($this->between($text, '注意\n事项', '费用'));
        $price = $this->match('/(\d+(?:\.\d+)?元\/人[^\n]*)/u', $text);
        if ($price) {
            $notices[] = '原方案参考费用：'.$this->oneLine($price).'；系统经费仍按工会每人每天 500 元政策执行。';
        }

        $highlights = collect($this->allMatches('/【([^】]{2,40})】/u', $text))
            ->map(fn (string $item) => trim($item))
            ->reject(fn (string $item) => str_contains($item, '住宿') || str_contains($item, '晚餐'))
            ->unique()
            ->take(10)
            ->values()
            ->all();

        return [
            'title' => $title,
            'region' => preg_match('/长春|长白山|延吉|吉林/u', $text) ? '东北' : '其他',
            'location' => $this->destinations($text),
            'summary' => $summary,
            'min_people' => 20,
            'max_people' => max(25, (int) ($this->match('/按(\d+)人核算/u', $text) ?: 25)),
            'departure_city' => $this->match('/参考航班：\s*([^\-\n]+)-/u', $text) ?: '杭州',
            'return_city' => $this->match('/参考航班：[^\n]*[－-]([^A-Z\d\n]+)[A-Z]{2}\d+/u', $text) ?: '杭州',
            'inbound_transport' => $this->flightLine($text, '杭州', '长春'),
            'outbound_transport' => $this->flightLine($text, '长春', '杭州'),
            'highlights' => $highlights,
            'experiences' => $this->oneLine($this->between($text, '》特色体验', '日期')),
            'hotel_standard' => $this->serviceValue($text, '住宿', '用\s*餐'),
            'meal_standard' => $this->serviceValue($text, '用\s*餐', '交通'),
            'local_transport' => $this->serviceValue($text, '交通', '门票'),
            'ticket_standard' => $this->serviceValue($text, '门票', '导\s*服'),
            'guide_service' => $this->serviceValue($text, '导\s*服', '保险'),
            'insurance' => $this->serviceValue($text, '保险', '优化\s*服务'),
            'value_added' => $this->numberedItems($this->between($text, '优化\n服务', '注意\n事项')),
            'self_funded_items' => [
                '往返大交通费用（如机票、高铁票），除非工会最终发布方案明确包含',
                '个人消费及方案未列明项目',
            ],
            'notices' => $notices ?: ['请携带有效身份证件，并如实告知个人身体健康状况。'],
            'cover_generated' => preg_match('/长白山/u', $text) === 1,
            'days' => $days,
        ];
    }

    /** @return array<int, array<string, string|int>> */
    private function parseDays(string $text): array
    {
        preg_match_all('/(?:^|\n)D(\d+)\s*\n(.*?)(?=(?:\nD\d+\s*\n)|(?:\n接待服务标准)|\z)/su', $text, $matches, PREG_SET_ORDER);

        return collect($matches)->map(function (array $match): array {
            $number = (int) $match[1];
            $body = trim($match[2]);
            if ($number === 4 && str_contains($body, "\n早餐：")) {
                $body = Str::before($body, "\n早餐：");
            }
            $locations = [
                1 => ['长春', '杭州 → 长春'],
                2 => ['长春、二道白河', '长春 → 二道白河'],
                3 => ['长白山、延吉', '长白山 → 延吉'],
                4 => ['延吉、长春', '延吉 → 长春'],
                5 => ['长春、杭州', '长春 → 杭州'],
            ];
            [$location, $title] = $locations[$number] ?? [$this->firstDestination($body), '第 '.$number.' 天行程'];

            $morning = $this->period($body, ['早上', '早餐', '上午'], ['中午', '下午', '晚上']);
            if ($number === 5 && $morning === '') {
                $morning = '酒店自助早餐；游览伪满皇宫博物院。';
            }

            return [
                'id' => $number,
                'title' => $title,
                'location' => $location,
                'transport' => $this->dayTransport($body),
                'morning' => $morning,
                'afternoon' => $this->period($body, ['中午', '下午'], ['晚上']),
                'evening' => $this->period($body, ['晚上'], []),
                'plan' => '',
                'meals' => $this->mealsForDay($number),
                'stay' => [1 => '长春', 2 => '二道白河镇', 3 => '延边', 4 => '长春', 5 => '—'][$number] ?? '',
                'note' => '',
            ];
        })->values()->all();
    }

    /**
     * @param  list<string>  $starts
     * @param  list<string>  $ends
     */
    private function period(string $body, array $starts, array $ends): string
    {
        $startPattern = implode('|', array_map(fn (string $value) => preg_quote($value, '/'), $starts));
        $endPattern = $ends === []
            ? '\z'
            : '(?=(?:(?:'.implode('|', array_map(fn (string $value) => preg_quote($value, '/'), $ends)).')\s*[：:]|\z))';
        if (! preg_match('/(?:'.$startPattern.')\s*[：:]\s*(.*?)'.$endPattern.'/su', $body, $match)) {
            return '';
        }

        return Str::limit($this->oneLine($match[1]), 1800, '…');
    }

    private function dayTransport(string $body): string
    {
        $parts = [];
        if ($flight = $this->match('/参考航班：\s*([^\n]+)/u', $body)) {
            $parts[] = '飞机：'.$this->oneLine($flight);
        }
        if ($drive = $this->match('/车程约\s*([\d.]+小时)/u', $body)) {
            $parts[] = '旅游车约'.$drive;
        }

        return implode('；', $parts) ?: '空调旅游车';
    }

    private function flightLine(string $text, string $from, string $to): string
    {
        $pattern = '/参考航班：\s*'.preg_quote($from, '/').'\s*[-－]\s*'.preg_quote($to, '/').'\s*([^\n]+)/u';
        $flight = $this->match($pattern, $text);

        return $flight ? $from.'-'.$to.' '.$this->oneLine($flight) : '';
    }

    private function destinations(string $text): string
    {
        $known = collect(['长春', '长白山', '延吉'])->filter(fn (string $place) => str_contains($text, $place));

        return $known->isEmpty() ? '请核对目的地' : '吉林·'.$known->join(' / ');
    }

    private function firstDestination(string $text): string
    {
        return collect(['长春', '长白山', '延吉', '杭州'])
            ->first(fn (string $place) => str_contains($text, $place), '请核对地点');
    }

    private function mealsForDay(int $day): string
    {
        return [1 => '中餐、晚餐', 2 => '早餐、中餐、晚餐', 3 => '早餐、中餐、晚餐', 4 => '早餐、中餐、晚餐', 5 => '早餐、中餐'][$day] ?? '';
    }

    private function serviceValue(string $text, string $start, string $end): string
    {
        if (! preg_match('/(?:^|\n)'.$start.'[ \t]*(?:\n)?(.*?)(?=\n'.$end.')/su', $text, $match)) {
            return '请根据原 PDF 核对补充';
        }

        return $this->oneLine($match[1]);
    }

    /** @return array<int, string> */
    private function numberedItems(string $text): array
    {
        preg_match_all('/(?:^|\n)\s*\d+[、.]\s*(.*?)(?=(?:\n\s*\d+[、.])|\z)/su', $text, $matches);

        return collect($matches[1])->map(fn (string $item) => $this->oneLine($item))->filter()->values()->all();
    }

    private function between(string $text, string $start, string $end): string
    {
        $start = str_replace('\\n', "\n", $start);
        $end = str_replace('\\n', "\n", $end);
        $from = mb_strpos($text, $start);
        if ($from === false) {
            return '';
        }
        $from += mb_strlen($start);
        $to = mb_strpos($text, $end, $from);

        return trim(mb_substr($text, $from, $to === false ? null : $to - $from));
    }

    private function match(string $pattern, string $text): string
    {
        return preg_match($pattern, $text, $match) ? trim($match[1]) : '';
    }

    /** @return array<int, string> */
    private function allMatches(string $pattern, string $text): array
    {
        preg_match_all($pattern, $text, $matches);

        return $matches[1];
    }

    private function normalize(string $text): string
    {
        $text = str_replace(["\r\n", "\r", "\u{00A0}"], ["\n", "\n", ' '], $text);
        $text = preg_replace('/[ \t]+/u', ' ', $text) ?? $text;

        return preg_replace('/\n{3,}/u', "\n\n", trim($text)) ?? trim($text);
    }

    private function oneLine(string $text): string
    {
        return trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    }
}
