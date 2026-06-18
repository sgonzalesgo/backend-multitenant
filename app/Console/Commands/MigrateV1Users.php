<?php

namespace App\Console\Commands;

use App\Models\Academic\Instructor;
use App\Models\Administration\User;
use App\Models\MigrationIdMap;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class MigrateV1Users extends Command
{
    protected $signature = 'migrate:v1-users {--fresh : Delete migrated users before importing again}';

    protected $description = 'Migrate users from v1 public.users to v2 users';

    public function handle(): int
    {
        $this->info('Starting users migration from v1...');

        if ($this->option('fresh')) {
            $ids = MigrationIdMap::query()
                ->where('entity', 'user')
                ->pluck('new_id');

            User::query()
                ->whereIn('id', $ids)
                ->delete();

            MigrationIdMap::query()
                ->where('entity', 'user')
                ->delete();
        }

        $users = DB::connection('pgsql_v1')
            ->table('users')
            ->orderBy('created_at')
            ->get();

        $created = 0;
        $skipped = 0;
        $failed = 0;

        DB::beginTransaction();

        try {
            foreach ($users as $oldUser) {
                if (
                    MigrationIdMap::query()
                        ->where('entity', 'user')
                        ->where('old_id', $oldUser->id)
                        ->exists()
                ) {
                    $skipped++;
                    continue;
                }

                $oldPersonId = DB::connection('pgsql_v1')
                    ->table('person_user')
                    ->where('user_id', $oldUser->id)
                    ->value('person_id');

                $personId = $this->mapId('person', $oldPersonId);

                if (! $oldPersonId) {
                    $this->warn("User {$oldUser->email} has no person_user relation.");
                }

                if ($oldPersonId && ! $personId) {
                    $this->warn("User {$oldUser->email} has old person {$oldPersonId}, but no migrated person map.");
                }

                $existingUser = User::query()
                    ->where('email', $oldUser->email)
                    ->first();

                if ($existingUser) {
                    $this->assignInstructorRoleIfApplies(
                        $existingUser->person_id,
                        $existingUser
                    );

                    MigrationIdMap::query()->create([
                        'entity' => 'user',
                        'old_id' => $oldUser->id,
                        'new_id' => $existingUser->id,
                        'metadata' => [
                            'matched_existing' => true,
                            'matched_by' => 'email',
                            'old_email' => $oldUser->email,
                            'old_person_id' => $oldPersonId,
                            'new_person_id' => $existingUser->person_id,
                        ],
                    ]);

                    $skipped++;
                    continue;
                }

                if ($personId) {
                    $userWithSamePerson = User::query()
                        ->where('person_id', $personId)
                        ->first();

                    if ($userWithSamePerson) {
                        $this->warn(
                            "Skipped user {$oldUser->email}: person {$personId} already linked to user {$userWithSamePerson->email}"
                        );

                        $this->assignInstructorRoleIfApplies(
                            $personId,
                            $userWithSamePerson
                        );

                        MigrationIdMap::query()->create([
                            'entity' => 'user',
                            'old_id' => $oldUser->id,
                            'new_id' => $userWithSamePerson->id,
                            'metadata' => [
                                'matched_existing' => true,
                                'matched_by' => 'person_id',
                                'old_email' => $oldUser->email,
                                'old_person_id' => $oldPersonId,
                                'new_person_id' => $personId,
                                'existing_user_email' => $userWithSamePerson->email,
                            ],
                        ]);

                        $skipped++;
                        continue;
                    }
                }

                $user = User::query()->create([
                    'person_id' => $personId,
                    'name' => $oldUser->name,
                    'email' => $oldUser->email,
                    'email_verified_at' => now(),
                    'password' => $oldUser->password,
                    'avatar' => $this->normalizeAvatarPath($oldUser->avatar ?? null),
                    'status' => ($oldUser->active ?? true) ? 'active' : 'inactive',
                    'google_id' => $oldUser->google_id ?? null,
                    'facebook_id' => null,
                    'instagram_id' => null,
                    'locale' => null,
                    'last_seen_at' => null,
                    'remember_token' => $oldUser->remember_token ?? null,
                    'created_at' => $oldUser->created_at ?? now(),
                    'updated_at' => $oldUser->updated_at ?? now(),
                ]);

                $this->assignInstructorRoleIfApplies($personId, $user);

                MigrationIdMap::query()->create([
                    'entity' => 'user',
                    'old_id' => $oldUser->id,
                    'new_id' => $user->id,
                    'metadata' => [
                        'old_email' => $oldUser->email,
                        'old_person_id' => $oldPersonId,
                        'new_person_id' => $personId,
                        'old_active' => $oldUser->active ?? null,
                    ],
                ]);

                $created++;
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->table(
            ['Created', 'Skipped', 'Failed'],
            [[$created, $skipped, $failed]]
        );

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function mapId(string $entity, ?string $oldId): ?string
    {
        if (! $oldId) {
            return null;
        }

        return MigrationIdMap::query()
            ->where('entity', $entity)
            ->where('old_id', $oldId)
            ->value('new_id');
    }

    private function normalizeAvatarPath(?string $avatar): ?string
    {
        $avatar = trim((string) $avatar);

        if ($avatar === '' || $avatar === 'default.png') {
            return null;
        }

        return str($avatar)
            ->replaceStart('avatars/', 'users/')
            ->toString();
    }

    private function assignInstructorRoleIfApplies(?string $personId, User $user): void
    {
        if (! $personId) {
            return;
        }

        $isInstructor = Instructor::query()
            ->where('person_id', $personId)
            ->exists();

        if (! $isInstructor) {
            return;
        }

        $role = Role::query()
            ->where('name', 'Instructor')
            ->first();

        if (! $role) {
            $this->warn('Role Instructor not found.');

            return;
        }

        if (! $user->hasRole('Instructor')) {
            $user->assignRole($role);
        }
    }
}
