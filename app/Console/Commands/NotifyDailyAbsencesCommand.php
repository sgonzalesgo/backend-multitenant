<?php

namespace App\Console\Commands;

use App\Mail\StudentAbsenceNotificationMail;
use App\Models\Academic\AttendanceRecord;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class NotifyDailyAbsencesCommand extends Command
{
    protected $signature = 'attendance:notify-daily-absences {date?}';

    protected $description = 'Send daily absence notifications to legal representatives.';

    public function handle(): int
    {
        $today = $this->argument('date') ?? now()->toDateString();

        $records = AttendanceRecord::query()
            ->with([
                'student.person',
                'student.legalRepresentatives.person',
                'attendanceSession.course',
                'attendanceSession.subject',
                'attendanceSession.instructor.person',
            ])
            ->where('status', 'absent')
            ->whereNull('absence_notified_at')
            ->whereHas('attendanceSession', function ($query) use ($today) {
                $query
                    ->whereDate('attendance_date', $today)
                    ->where('status', 'closed');
            })
            ->get();

        $this->info('Today: ' . $today);
        $this->info('Records found: ' . $records->count());

        foreach ($records as $record) {
            $this->info('Record ID: ' . $record->id);
            $this->info('Student: ' . ($record->student?->person?->full_name ?? 'N/A'));
            $this->info('Session status: ' . ($record->attendanceSession?->status ?? 'N/A'));
            $this->info('Attendance date: ' . ($record->attendanceSession?->attendance_date?->toDateString() ?? 'N/A'));
            $this->info('Representatives: ' . $record->student?->legalRepresentatives?->count());
        }

        if ($records->isEmpty()) {
            $this->info('No absences pending notification.');

            return self::SUCCESS;
        }

        $recordsByStudent = $records->groupBy('student_id');

        foreach ($recordsByStudent as $studentId => $studentRecords) {
            $student = $studentRecords->first()->student;

            if (! $student) {
                continue;
            }

            foreach ($student->legalRepresentatives as $representative) {
                $representativePerson = $representative->person;

                if (! $representativePerson || ! $representativePerson->email) {
                    continue;
                }

                Mail::to($representativePerson->email)
                    ->send(new StudentAbsenceNotificationMail(
                        student: $student,
                        absenceRecords: $studentRecords,
                        representativeName: $representativePerson->full_name
                    ));
            }

            AttendanceRecord::query()
                ->whereIn('id', $studentRecords->pluck('id'))
                ->update([
                    'absence_notified_at' => now(),
                ]);
        }

        $this->info('Daily absence notifications sent successfully.');

        return self::SUCCESS;
    }
}
