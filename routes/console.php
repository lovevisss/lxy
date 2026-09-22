<?php

use App\Models\RetreatGroup;
use App\Services\RetreatGroupApprovalService;
use App\Services\RetreatGroupLifecycleService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Validation\ValidationException;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('retreat:process-deadlines', function (RetreatGroupLifecycleService $lifecycle, RetreatGroupApprovalService $approval) {
    $groups = RetreatGroup::query()
        ->with('applications')
        ->where('status', 'open')
        ->where('approval_status', 'not_submitted')
        ->whereDate('application_deadline', '<', today())
        ->get();

    foreach ($groups as $group) {
        $joined = (int) $group->applications->where('status', 'approved')->sum('member_count');

        if ($joined >= $group->min_people) {
            try {
                $approval->submit($group, $group->leader);
                $this->info("{$group->title}：已自动提交成团审批");
            } catch (ValidationException $exception) {
                $this->warn("{$group->title}：无法提交审批，".collect($exception->errors())->flatten()->join('；'));
            }
        } else {
            $lifecycle->fail($group, "报名截止时已确认 {$joined} 人，未达到最低成团人数 {$group->min_people} 人");
            $this->info("{$group->title}：已标记未成团并生成通知短信任务");
        }
    }

    $this->info("共处理 {$groups->count()} 个到期团队");
})->purpose('处理报名截止的疗休养团队并生成短信通知任务');

Schedule::command('retreat:process-deadlines')->hourly()->withoutOverlapping();
Schedule::command('retreat:sync-teachers')->dailyAt('02:10')->withoutOverlapping();
