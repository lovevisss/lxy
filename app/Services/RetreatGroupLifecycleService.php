<?php

namespace App\Services;

use App\Models\RetreatGroup;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RetreatGroupLifecycleService
{
    public function __construct(private readonly RetreatSmsService $sms) {}

    public function form(RetreatGroup $group): int
    {
        if ($group->status !== 'open') {
            throw ValidationException::withMessages(['action' => '当前团队状态无法执行成团操作。']);
        }

        $group->loadMissing('applications');
        $joined = (int) $group->applications->where('status', 'approved')->sum('member_count');
        if ($joined < $group->min_people) {
            throw ValidationException::withMessages([
                'action' => "当前已确认 {$joined} 人，未达到最低成团人数 {$group->min_people} 人。",
            ]);
        }

        return DB::transaction(function () use ($group): int {
            $departureLimit = $group->departure_date->copy()->subDay()->endOfDay();
            $preferredDeadline = now()->addDays(3);
            $confirmationDeadline = $departureLimit->isFuture()
                ? $preferredDeadline->min($departureLimit)
                : now()->addHours(12);

            $group->update([
                'status' => 'formed',
                'status_reason' => null,
                'formed_at' => now(),
                'final_confirmation_deadline' => $confirmationDeadline,
            ]);

            $approved = $group->applications->where('status', 'approved');
            foreach ($approved as $application) {
                $isLeader = $application->user_id === $group->leader_id;
                $application->update([
                    'final_confirmation_status' => $isLeader ? 'confirmed' : 'pending',
                    'final_confirmation_at' => $isLeader ? now() : null,
                ]);
            }

            $recipients = $approved->where('user_id', '!=', $group->leader_id)->values();
            $content = "【高校疗休养】“{$group->title}”已成团，请于{$confirmationDeadline->format('m月d日 H:i')}前登录系统完成最终参团确认。";

            return $this->sms->queueForApplications($group, $recipients, 'formed_confirmation', $content);
        });
    }

    public function fail(RetreatGroup $group, string $reason): int
    {
        if ($group->status !== 'open') {
            throw ValidationException::withMessages(['action' => '当前团队状态无法标记为未成团。']);
        }

        return DB::transaction(function () use ($group, $reason): int {
            $group->loadMissing('applications');
            $group->update([
                'status' => 'failed',
                'status_reason' => $reason,
                'failed_at' => now(),
                'final_confirmation_deadline' => null,
            ]);
            $group->applications()->update([
                'final_confirmation_status' => 'not_required',
                'final_confirmation_at' => null,
            ]);

            $recipients = $group->applications
                ->whereIn('status', ['pending', 'approved'])
                ->where('user_id', '!=', $group->leader_id)
                ->values();
            $content = "【高校疗休养】“{$group->title}”未能成团，本次行程不再组织。原因：{$reason}。请登录系统查看详情。";

            return $this->sms->queueForApplications($group, $recipients, 'formation_failed', $content);
        });
    }

    public function cancel(RetreatGroup $group, string $reason): int
    {
        if (! in_array($group->status, ['open', 'formed'], true)) {
            throw ValidationException::withMessages(['action' => '当前团队状态无法取消。']);
        }

        return DB::transaction(function () use ($group, $reason): int {
            $group->loadMissing('applications');
            $group->update([
                'status' => 'cancelled',
                'status_reason' => $reason,
                'cancelled_at' => now(),
                'final_confirmation_deadline' => null,
            ]);
            $group->applications()->update([
                'final_confirmation_status' => 'not_required',
                'final_confirmation_at' => null,
            ]);

            $recipients = $group->applications
                ->whereIn('status', ['pending', 'approved'])
                ->where('user_id', '!=', $group->leader_id)
                ->values();
            $content = "【高校疗休养】团长已取消“{$group->title}”。原因：{$reason}。请登录系统查看详情。";

            return $this->sms->queueForApplications($group, $recipients, 'group_cancelled', $content);
        });
    }

    public function remind(RetreatGroup $group): int
    {
        if ($group->status !== 'formed') {
            throw ValidationException::withMessages(['action' => '只有已成团团队可以发送最终确认提醒。']);
        }

        $group->loadMissing('applications');
        $recipients = $group->applications
            ->where('status', 'approved')
            ->where('final_confirmation_status', 'pending')
            ->where('user_id', '!=', $group->leader_id)
            ->values();

        if ($recipients->isEmpty()) {
            throw ValidationException::withMessages(['action' => '当前没有待最终确认的团员。']);
        }

        $deadline = $group->final_confirmation_deadline?->format('m月d日 H:i') ?? '出发前';
        $content = "【高校疗休养】提醒：请于{$deadline}前登录系统，完成“{$group->title}”最终参团确认。";

        return $this->sms->queueForApplications($group, $recipients, 'confirmation_reminder', $content);
    }
}
