<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;
use stdClass;

class TeacherDirectoryService
{
    private const TABLE = 't_cx_zzqxryxx';

    private ?string $placeholderPasswordHash = null;

    /** @param array<int, string> $identifiers */
    public function findEligible(array $identifiers): ?stdClass
    {
        $identifiers = collect($identifiers)
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->unique()
            ->values();

        if ($identifiers->isEmpty()) {
            return null;
        }

        return DB::connection('middata')
            ->table(self::TABLE)
            ->select(['xgh', 'xm', 'dwmc', 'dwbm', 'dzyx', 'yddh'])
            ->where('rylx', '1')
            ->whereIn('xgh', $identifiers)
            ->first();
    }

    public function syncTeacher(stdClass $teacher, ?string $casSubject = null): User
    {
        $staffNumber = trim((string) $teacher->xgh);
        if ($staffNumber === '') {
            throw new RuntimeException('教师清单中的工号不能为空。');
        }

        $user = User::query()->firstOrNew(['staff_number' => $staffNumber]);
        if (! $user->exists) {
            $user->password = $this->placeholderPasswordHash ??= Hash::make(Str::random(64));
            $user->role = 'teacher';
            $user->identity_source = 'cas';
        }

        $user->fill([
            'email' => Str::lower($staffNumber).'@zufedfc.edu.cn',
            'name' => trim((string) $teacher->xm),
            'department' => trim((string) $teacher->dwmc) ?: null,
            'directory_email' => trim((string) $teacher->dzyx) ?: null,
            'mobile' => trim((string) $teacher->yddh) ?: null,
            'retreat_eligible' => true,
            'cas_synced_at' => now(),
        ]);
        if ($casSubject) {
            $user->cas_subject = $casSubject;
        }
        $user->email_verified_at ??= now();
        $user->save();

        return $user;
    }

    /** @return array{synced: int, deactivated: int} */
    public function syncAll(): array
    {
        $teachers = DB::connection('middata')
            ->table(self::TABLE)
            ->select(['xgh', 'xm', 'dwmc', 'dwbm', 'dzyx', 'yddh'])
            ->where('rylx', '1')
            ->whereNotNull('xgh')
            ->orderBy('xgh')
            ->get();

        if ($teachers->isEmpty()) {
            throw new RuntimeException('教师资格清单为空，已停止同步以避免误停用账号。');
        }

        $staffNumbers = $teachers
            ->pluck('xgh')
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->unique()
            ->values();

        $deactivated = User::query()
            ->where('identity_source', 'cas')
            ->whereNotIn('staff_number', $staffNumbers)
            ->where('retreat_eligible', true)
            ->update(['retreat_eligible' => false]);

        foreach ($teachers as $teacher) {
            $this->syncTeacher($teacher);
        }

        return ['synced' => $staffNumbers->count(), 'deactivated' => $deactivated];
    }
}
