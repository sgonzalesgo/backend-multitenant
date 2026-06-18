<?php

namespace App\Jobs\Academic;

use App\Models\Academic\AcademicSchedule;
use App\Repositories\Academic\AcademicScheduleRepository;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class GenerateAcademicScheduleCalendarEventsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 1200;

    public int $tries = 1;

    public function __construct(
        public string $academicScheduleId,
        public string $tenantId
    ) {}

    public function handle(AcademicScheduleRepository $repository): void
    {
        $academicSchedule = AcademicSchedule::query()
            ->where('tenant_id', $this->tenantId)
            ->where('id', $this->academicScheduleId)
            ->first();

        if (! $academicSchedule) {
            return;
        }

        try {
            $repository->generateCalendarEventsForSchedule($academicSchedule);
        } catch (Throwable $e) {
            $academicSchedule->forceFill([
                'calendar_sync_status' => 'failed',
                'calendar_sync_error' => $e->getMessage(),
                'calendar_sync_progress' => 0,
            ])->save();

            throw $e;
        }
    }

    public function failed(Throwable $exception): void
    {
        AcademicSchedule::query()
            ->where('tenant_id', $this->tenantId)
            ->where('id', $this->academicScheduleId)
            ->update([
                'calendar_sync_status' => 'failed',
                'calendar_sync_error' => $exception->getMessage(),
            ]);
    }
}
