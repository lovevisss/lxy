<?php

namespace Tests\Unit;

use App\Services\RetreatRoutePdfParser;
use PHPUnit\Framework\TestCase;

class RetreatRoutePdfParserTest extends TestCase
{
    public function test_it_parses_a_travel_plan_into_an_editable_route_draft(): void
    {
        $text = <<<'TEXT'
【长春长白山延吉五日疗休养方案】
印象·长白山
森林生态与民俗文化相结合的疗休养线路。
》景点介绍
》特色体验
朝鲜族民俗体验
日期
D1
参考航班：杭州-长春CZ6546 11:50-15:00
上午：杭州集合乘机前往长春。
下午：参观长春电影制片厂。
晚上：入住酒店。
D2
上午：乘车前往二道白河（车程约4.5小时）
下午：森林漂流。
晚上：东北特色餐。
接待服务标准
住宿
网评四钻标准酒店2人1间
用
餐
含酒店早餐及正餐
交通
全程空调旅游车
门票
含行程中景点大门票
导
服
全程专业中文导游服务
保险
旅行社责任险、旅游意外保险
优化
服务
1、每日矿泉水；
注意
事项
1、请携带有效身份证件；
费用
3000元/人（按25人核算标准成团）
TEXT;

        $draft = (new RetreatRoutePdfParser)->parseText($text);

        $this->assertSame('长春长白山延吉五日疗休养', $draft['title']);
        $this->assertSame('东北', $draft['region']);
        $this->assertSame(25, $draft['max_people']);
        $this->assertCount(2, $draft['days']);
        $this->assertSame('网评四钻标准酒店2人1间', $draft['hotel_standard']);
        $this->assertStringContainsString('500 元', $draft['notices'][1]);
        $this->assertTrue($draft['cover_generated']);
    }
}
