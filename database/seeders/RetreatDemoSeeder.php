<?php

namespace Database\Seeders;

use App\Models\RetreatGroup;
use App\Models\RetreatGroupApplication;
use App\Models\RetreatRoute;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RetreatDemoSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@lxy.edu.cn'],
            [
                'name' => '系统管理员',
                'password' => Hash::make('LxyAdmin@2026'),
                'email_verified_at' => now(),
                'role' => 'admin',
                'department' => '校工会',
            ],
        );

        $teacher = User::updateOrCreate(
            ['email' => 'teacher@lxy.edu.cn'],
            [
                'name' => '陈老师',
                'password' => Hash::make('Teacher@2026'),
                'email_verified_at' => now(),
                'role' => 'teacher',
                'department' => '信息工程学院',
            ],
        );

        User::updateOrCreate(
            ['email' => 'member@lxy.edu.cn'],
            [
                'name' => '李老师',
                'password' => Hash::make('Member@2026'),
                'email_verified_at' => now(),
                'role' => 'teacher',
                'department' => '人文学院',
            ],
        );

        $route = RetreatRoute::updateOrCreate(
            ['title' => '北国风光 · 长春长白山延吉五日'],
            [
                'creator_id' => $teacher->id,
                'region' => '东北',
                'location' => '吉林 · 长春 / 长白山 / 延吉',
                'summary' => '从长春城市记忆出发，深入长白山火山地貌与森林生态，再到延吉体验朝鲜族文化与城市烟火。',
                'days' => 5,
                'min_people' => 20,
                'max_people' => 25,
                'departure_city' => '杭州',
                'return_city' => '杭州',
                'inbound_transport' => '杭州—长春 CZ6546，参考时刻 11:50—15:00',
                'outbound_transport' => '长春—杭州 CZ6405，参考时刻 16:00—19:05',
                'highlights' => ['长白山天池', '朝鲜族文化', '森林康养'],
                'experiences' => '长春电影文化、森林漂流、朝鲜族民俗体验及东北特色餐饮。',
                'hotel_standard' => '全程网评四钻标准酒店，2 人 1 间；长春、二道白河、延吉安排同等级酒店。',
                'meal_standard' => '含 4 次酒店早餐、9 次正餐，安排东北铁锅炖、延吉烤肉等特色餐。',
                'local_transport' => '全程空调旅游车，根据团队人数安排合适车型。',
                'ticket_standard' => '包含行程所列景点首道门票及长白山环线车、环保车。',
                'guide_service' => '全程专业中文导游服务，关键景点提供讲解。',
                'insurance' => '旅行社责任险及旅游意外保险，具体保额以最终保单为准。',
                'value_added' => ['提供旅行用品、饮用水及常备外用药品', '全程不安排购物点'],
                'self_funded_items' => ['杭州往返长春机票及机场往返交通', '个人消费、行李超额费及方案未列明项目'],
                'notices' => ['携带有效身份证件', '根据长白山气候准备保暖及防雨衣物', '如实告知影响出行安全的健康情况'],
                'cover_path' => '/images/retreat/changchun-changbaishan-yanji-cover.png',
                'status' => 'approved',
                'current_stage' => null,
                'submitted_at' => now()->subDays(8),
                'approved_at' => now()->subDays(5),
            ],
        );

        $days = [
            ['飞抵长春 · 城市电影记忆', '杭州—长春', '参考航班 CZ6546', '集合乘机前往长春。', '游览长春电影制片厂旧厂区。', '体验“这有山”文旅街区。', '含中餐、晚餐', '长春网评四钻酒店', '航班以出票为准'],
            ['林海穿行 · 二道白河体验', '长春—二道白河', '乘车约 4.5 小时', '乘车前往二道白河。', '安排森林漂流与恩都里文化街区。', '品尝东北铁锅炖。', '含早、中、晚餐', '二道白河网评四钻酒店', '漂流根据天气和水情调整'],
            ['长白山天池 · 延吉夜色', '长白山—延吉', '景区环保车及旅游车', '游览长白山北景区。', '游览天池、瀑布、绿渊潭等。', '抵达延吉并体验城市夜景。', '含早、中、晚餐', '延吉网评四钻酒店', '天池开放情况受天气影响'],
            ['朝鲜族民俗 · 返回长春', '延吉—长春', '乘车约 4.5 小时', '自由体验水上市场及民俗园。', '乘车返回长春。', '入住后休息。', '含早、中、晚餐', '长春网评四钻酒店', '民俗体验以现场安排为准'],
            ['历史教育 · 从容返杭', '长春—杭州', '参考航班 CZ6405', '参观伪满皇宫博物院。', '午餐后前往机场。', '返回杭州。', '含早餐、中餐', '—', '返程交通以最终出票为准'],
        ];

        foreach ($days as $index => $day) {
            $route->itineraryDays()->updateOrCreate(
                ['day_number' => $index + 1],
                [
                    'title' => $day[0],
                    'location' => $day[1],
                    'transport' => $day[2],
                    'morning' => $day[3],
                    'afternoon' => $day[4],
                    'evening' => $day[5],
                    'meals' => $day[6],
                    'stay' => $day[7],
                    'note' => $day[8],
                ],
            );
        }

        $pendingRoute = RetreatRoute::firstOrCreate(
            ['title' => '竹海清风 · 莫干山三日疗休养'],
            [
                'creator_id' => $teacher->id,
                'region' => '华东',
                'location' => '浙江 · 湖州 / 莫干山',
                'summary' => '以竹林慢行、山居休整和乡村文化体验为主线的短途疗休养线路。',
                'days' => 3,
                'min_people' => 12,
                'max_people' => 24,
                'departure_city' => '杭州',
                'return_city' => '杭州',
                'inbound_transport' => '学校统一乘坐旅游大巴',
                'outbound_transport' => '旅游大巴统一返校',
                'highlights' => ['竹林慢行', '山居静养', '乡村文化'],
                'experiences' => '竹林自然课堂、轻量徒步及团队交流。',
                'hotel_standard' => '莫干山舒适型度假酒店双人标准间。',
                'meal_standard' => '含 2 次早餐、5 次正餐。',
                'local_transport' => '全程空调旅游车。',
                'ticket_standard' => '包含行程所列景点首道门票。',
                'guide_service' => '全程中文导游及安全联络服务。',
                'insurance' => '旅行社责任险及旅游意外保险。',
                'value_added' => ['每日饮用水', '常用外用药品'],
                'self_funded_items' => ['往返大交通费用（如机票、高铁票）', '个人消费及方案未列明项目'],
                'notices' => ['穿着防滑运动鞋', '携带有效身份证件'],
                'status' => 'pending_department',
                'current_stage' => 'department',
                'submitted_at' => now()->subHours(2),
            ],
        );

        $pendingDays = [
            ['集合出发 · 山居初见', '杭州—莫干山', '旅游大巴', '学校集合出发。', '入住后竹林适应性慢行。', '团队行程说明会。', '含中餐、晚餐', '莫干山度假酒店'],
            ['竹海晨行 · 自然课堂', '莫干山', '景区接驳车', '竹林晨行与舒展。', '自然观察和乡村文化体验。', '自由休整。', '含早、中、晚餐', '莫干山度假酒店'],
            ['山居回顾 · 从容返校', '莫干山—杭州', '旅游大巴', '团队回顾及自由活动。', '午餐后统一返校。', null, '含早餐、中餐', '—'],
        ];

        foreach ($pendingDays as $index => $day) {
            $pendingRoute->itineraryDays()->updateOrCreate(
                ['day_number' => $index + 1],
                [
                    'title' => $day[0],
                    'location' => $day[1],
                    'transport' => $day[2],
                    'morning' => $day[3],
                    'afternoon' => $day[4],
                    'evening' => $day[5],
                    'meals' => $day[6],
                    'stay' => $day[7],
                ],
            );
        }

        $group = RetreatGroup::updateOrCreate(
            ['title' => '十月长白山延吉疗休养团'],
            [
                'retreat_route_id' => $route->id,
                'leader_id' => $admin->id,
                'departure_date' => '2026-10-25',
                'return_date' => '2026-10-29',
                'application_deadline' => '2026-10-15',
                'min_people' => 20,
                'max_people' => 25,
                'approval_mode' => 'manual',
                'meeting_info' => '学校东门 07:30 集合，统一乘车前往机场。',
                'notes' => '报名时请确认本人及随行家属信息。',
                'status' => 'open',
            ],
        );

        RetreatGroupApplication::updateOrCreate(
            ['retreat_group_id' => $group->id, 'user_id' => $admin->id],
            [
                'member_count' => 1,
                'family_members' => [],
                'message' => '团长创建组团时自动加入',
                'contact_mobile' => '13800000001',
                'status' => 'approved',
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
                'review_comment' => '系统自动确认团长成员资格',
            ],
        );

        RetreatGroupApplication::updateOrCreate(
            ['retreat_group_id' => $group->id, 'user_id' => $teacher->id],
            ['member_count' => 2, 'family_members' => [['name' => '陈老师家属', 'relationship' => '配偶']], 'message' => '本人携一名家属参加。', 'contact_mobile' => '13800000002', 'status' => 'pending'],
        );

        $endedGroup = RetreatGroup::updateOrCreate(
            ['title' => '九月长白山延吉疗休养回顾团'],
            [
                'retreat_route_id' => $route->id,
                'leader_id' => $admin->id,
                'departure_date' => now()->subDays(10)->toDateString(),
                'return_date' => now()->subDays(6)->toDateString(),
                'application_deadline' => now()->subDays(20)->toDateString(),
                'min_people' => 20,
                'max_people' => 25,
                'approval_mode' => 'manual',
                'meeting_info' => '本团行程已顺利结束。',
                'notes' => '欢迎已参团成员提交真实体验评价。',
                'status' => 'formed',
            ],
        );

        foreach ([$admin, $teacher] as $member) {
            RetreatGroupApplication::updateOrCreate(
                ['retreat_group_id' => $endedGroup->id, 'user_id' => $member->id],
                [
                    'member_count' => 1,
                    'family_members' => [],
                    'message' => $member->id === $admin->id ? '团长自动加入' : '已确认参团',
                    'contact_mobile' => $member->id === $admin->id ? '13800000001' : '13800000002',
                    'status' => 'approved',
                    'final_confirmation_status' => 'confirmed',
                    'final_confirmation_at' => now()->subDays(18),
                    'reviewed_by' => $admin->id,
                    'reviewed_at' => now()->subDays(18),
                    'review_comment' => '报名已确认',
                ],
            );
        }
    }
}
