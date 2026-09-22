<?php

namespace App\Services;

use RuntimeException;
use Smalot\PdfParser\Parser;

/** Parses the common 康远 itinerary format without assuming a particular destination. */
class RetreatBatchPdfRouteParser
{
    /** @return array{title: string, days: array<int, array<string, mixed>>, hotel_standard: string, meal_standard: string, local_transport: string, ticket_standard: string, guide_service: string, insurance: string, highlights: array<int, string>} */
    public function parse(string $path, int $expectedDays): array
    {
        if (! is_file($path) || ! is_readable($path) || file_get_contents($path, false, null, 0, 5) !== '%PDF-') {
            throw new RuntimeException('PDF 文件不可读取或格式无效：'.basename($path));
        }

        $text = str_replace(["\r\n", "\r", "\u{00A0}"], ["\n", "\n", ' '], (new Parser)->parseFile($path)->getText());
        if (mb_strlen($text) < 100) {
            throw new RuntimeException('PDF 未提取到足够文字：'.basename($path));
        }

        if (! preg_match('/【([^】]*疗休养[^】]*)】/u', $text, $titleMatch)) {
            throw new RuntimeException('未识别线路标题：'.basename($path));
        }
        $title = $this->oneLine($titleMatch[1]);
        $title = preg_replace('/(?<=\p{Han})\s+(?=\p{Han})/u', '', $title) ?? $title;
        if (mb_strlen($title) > 100) {
            throw new RuntimeException('线路标题超过系统长度限制：'.basename($path));
        }

        $serviceStart = mb_strpos($text, '服务标准');
        if ($serviceStart === false) {
            throw new RuntimeException('未识别服务标准：'.basename($path));
        }
        $itinerary = mb_substr($text, 0, $serviceStart);
        preg_match_all('/(?:^|\n)D(\d+)\s*\n(.*?)(?=(?:\nD\d+\s*\n)|\z)/su', $itinerary, $matches, PREG_SET_ORDER);
        if (count($matches) !== $expectedDays) {
            throw new RuntimeException('逐日行程数量与文件名不符：'.basename($path));
        }

        $days = [];
        foreach ($matches as $index => $match) {
            $number = (int) $match[1];
            // A source PDF can contain a repeated D-number; preserve its text while
            // assigning consecutive system day numbers in document order.
            $systemDay = $index + 1;
            $body = trim(preg_replace('/[ \t]+/u', ' ', $match[2]) ?? $match[2]);
            $body = trim(preg_replace('/[—－-]+\s*$/u', '', $body) ?? $body);
            if (mb_strlen($body) < 30 || mb_strlen($body) > 3000) {
                throw new RuntimeException("第 {$systemDay} 天行程文字异常：".basename($path));
            }
            preg_match_all('/【([^】]{2,30})】/u', $body, $attractions);
            $dayTitle = array_map(fn (string $name) => $this->oneLine($name), $attractions[1] ?? []);
            $days[] = [
                'day_number' => $systemDay,
                'title' => '第'.$systemDay.'天'.($dayTitle ? ' · '.implode('、', array_slice($dayTitle, 0, 2)) : ' · 行程安排'),
                'plan' => $body,
                'note' => $number === $systemDay ? null : "原 PDF 此段标为 D{$number}，系统按文档顺序列为第 {$systemDay} 天。",
            ];
        }

        $service = mb_substr($text, $serviceStart);
        $hotel = $this->section($service, '（1）', '（2）');
        $meal = $this->section($service, '（2）', '（3）');
        $transport = $this->section($service, '（3）', '（4）');
        $guide = $this->section($service, '（4）', '（5）');
        $ticket = $this->section($service, '（5）', '（6）');
        $insurance = $this->section($service, '（6）', '——');

        foreach ([$hotel, $meal, $transport, $guide, $ticket, $insurance] as $part) {
            if ($part === '') {
                throw new RuntimeException('服务标准栏目不完整：'.basename($path));
            }
        }

        preg_match_all('/【([^】]{2,35})】/u', $itinerary, $highlightMatches);
        $highlights = array_values(array_unique(array_slice($highlightMatches[1] ?? [], 1, 8)));

        return [
            'title' => $title,
            'days' => $days,
            'hotel_standard' => $this->serviceExcerpt($hotel, '住宿等级', '房间要求'),
            'meal_standard' => $this->serviceExcerpt($meal, '餐标设定', '卫生承诺'),
            'local_transport' => $this->serviceExcerpt($transport, '车辆资质', '驾驶'),
            'ticket_standard' => $this->serviceExcerpt($ticket, '门票服务', null),
            'guide_service' => $this->serviceExcerpt($guide, '导游服务', '服务能力'),
            'insurance' => $this->serviceExcerpt($insurance, '保险类型', '理赔服务'),
            'highlights' => $highlights,
        ];
    }

    private function section(string $text, string $start, string $end): string
    {
        $from = mb_strpos($text, $start);
        if ($from === false) {
            return '';
        }
        $to = mb_strpos($text, $end, $from + mb_strlen($start));

        return trim(mb_substr($text, $from, $to === false ? null : $to - $from));
    }

    private function serviceExcerpt(string $section, string $start, ?string $end): string
    {
        $from = mb_strpos($section, $start);
        if ($from === false) {
            return $this->oneLine(mb_substr($section, 0, 800));
        }
        $from += mb_strlen($start);
        $to = $end ? mb_strpos($section, $end, $from) : false;
        $value = $this->oneLine(mb_substr($section, $from, $to === false ? 800 : $to - $from));

        return mb_substr($value, 0, 1500);
    }

    private function oneLine(string $text): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? $text);

        return preg_replace('/(?<=\p{Han})\s+(?=\p{Han})/u', '', $text) ?? $text;
    }
}
