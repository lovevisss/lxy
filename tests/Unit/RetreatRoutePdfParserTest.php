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

    public function test_it_parses_a_date_segmented_departure_notice(): void
    {
        $text = <<<'TEXT'
长春长白山五日疗休养出团通知书
（7月31日-8月4日）
人数：31+1
日程行程用餐
7.31
周一
早上：杭州集合乘机前往长春。
下午：游览【长春电影制片厂旧厂区】。
晚上：入住酒店休息。
8.1
周二
早餐：酒店自助早餐
上午：乘车前往二道白河。
下午：游览【森林漂流】。
费用说明
【包含项目】费用
提供服务标准:
1.交通：杭州-长春往返航班，当地空调旅游大巴车
2.住宿：携程四钻酒店2人1间
3.门票：含行程中所列首道大门票
4.餐费：全程含4早9正餐
5.保险：旅行社责任险、团体旅游意外险
6.导服：疗休养当地中文导游服务
【不含项目】
单人房差，个人消费及以上未提及的任何费用
温馨提示:
1.请携带有效身份证件。
“文明旅游我先行”
TEXT;

        $draft = (new RetreatRoutePdfParser)->parseText($text);

        $this->assertCount(2, $draft['days']);
        $this->assertSame(32, $draft['max_people']);
        $this->assertSame('携程四钻酒店2人1间', $draft['hotel_standard']);
        $this->assertSame('全程含4早9正餐', $draft['meal_standard']);
        $this->assertSame('杭州-长春往返航班，当地空调旅游大巴车', $draft['local_transport']);
        $this->assertSame(['单人房差', '个人消费及以上未提及的任何费用'], $draft['self_funded_items']);
    }
}
