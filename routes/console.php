<?php

use App\Models\RetreatGroup;
use App\Services\RetreatGroupLifecycleService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('retreat:process-deadlines', function (RetreatGroupLifecycleService $lifecycle) {
    $groups = RetreatGroup::query()
        ->with('applications')
        ->where('status', 'open')
        ->whereDate('application_deadline', '<', today())
        ->get();

    foreach ($groups as $group) {
        $joined = (int) $group->applications->where('status', 'approved')->sum('member_count');

        if ($joined >= $group->min_people) {
            $lifecycle->form($group);
            $this->info("{$group->title}：已自动成团并生成确认短信任务");
        } else {
            $lifecycle->fail($group, "报名截止时已确认 {$joined} 人，未达到最低成团人数 {$group->min_people} 人");
            $this->info("{$group->title}：已标记未成团并生成通知短信任务");
        }
    }

    $this->info("共处理 {$groups->count()} 个到期团队");
})->purpose('处理报名截止的疗休养团队并生成短信通知任务');

Schedule::command('retreat:process-deadlines')->hourly()->withoutOverlapping();
