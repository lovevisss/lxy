<?php

namespace App\Console\Commands;

use App\Services\TeacherDirectoryService;
use Illuminate\Console\Command;
use Throwable;

class SyncEligibleTeachers extends Command
{
    protected $signature = 'retreat:sync-teachers';

    protected $description = '从中间库同步可参加疗休养的教师账号';

    public function handle(TeacherDirectoryService $directory): int
    {
        try {
            $result = $directory->syncAll();
        } catch (Throwable $exception) {
            $this->error('教师清单同步失败：'.$exception->getMessage());

            return self::FAILURE;
        }

        $this->info("已同步 {$result['synced']} 名教师，停用 {$result['deactivated']} 个失效资格账号。");

        return self::SUCCESS;
    }
}
