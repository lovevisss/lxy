<?php

namespace App\Services;

use App\Models\RetreatGroup;
use App\Models\RetreatGroupApplication;
use App\Models\RetreatSmsNotification;
use Illuminate\Support\Collection;

class RetreatSmsService
{
    public function queueForApplications(
        RetreatGroup $group,
        Collection $applications,
        string $event,
        string $content,
    ): int {
        $queued = 0;

        foreach ($applications as $application) {
            /** @var RetreatGroupApplication $application */
            RetreatSmsNotification::create([
                'retreat_group_id' => $group->id,
                'retreat_group_application_id' => $application->id,
                'user_id' => $application->user_id,
                'mobile' => $application->contact_mobile,
                'event' => $event,
                'content' => $content,
                'status' => $application->contact_mobile ? 'pending_provider' : 'missing_mobile',
                'provider' => 'china_mobile',
                'metadata' => [
                    'provider_state' => 'awaiting_api_documentation',
                    'member_count' => $application->member_count,
                ],
            ]);
            $queued++;
        }

        return $queued;
    }
}
