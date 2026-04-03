<?php

namespace Database\Seeders\Calendar;

use App\Models\Administration\Tenant;
use App\Models\Calendar\CalendarEventType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CalendarEventTypeSeeder extends Seeder
{
    public function run(): void
    {
        $tenants = Tenant::query()->get();

        if ($tenants->isEmpty()) {
            $this->command?->warn('No tenants found. CalendarEventTypeSeeder skipped.');
            return;
        }

        $types = $this->defaultTypes();

        foreach ($tenants as $tenant) {
            foreach ($types as $type) {
                CalendarEventType::query()->updateOrCreate(
                    [
                        'tenant_id' => $tenant->id,
                        'code' => $type['code'],
                    ],
                    [
                        'name' => $type['name'],
                        'description' => $type['description'],
                        'color' => $type['color'],
                        'icon' => $type['icon'],
                        'is_system' => $type['is_system'],
                        'is_active' => $type['is_active'],
                        'settings' => $type['settings'],
                    ]
                );
            }
        }

        $this->command?->info('Calendar event types seeded successfully.');
    }

    protected function defaultTypes(): array
    {
        return [
            [
                'code' => 'class_shift',
                'name' => 'Class Shift',
                'description' => 'Regular class schedule, shifts, or academic timetable blocks.',
                'color' => '#1976d2',
                'icon' => 'ri-time-line',
                'is_system' => true,
                'is_active' => true,
                'settings' => [
                    'category' => 'academic',
                    'requires_location' => true,
                    'requires_audience' => true,
                    'supports_attendance' => false,
                    'supports_response' => false,
                    'supports_recurrence' => true,
                    'default_all_day' => false,
                ],
            ],
            [
                'code' => 'midterm_exam',
                'name' => 'Midterm Exam',
                'description' => 'Partial or midterm exam for a subject, course, or section.',
                'color' => '#d32f2f',
                'icon' => 'ri-file-list-3-line',
                'is_system' => true,
                'is_active' => true,
                'settings' => [
                    'category' => 'academic',
                    'requires_location' => true,
                    'requires_audience' => true,
                    'supports_attendance' => false,
                    'supports_response' => false,
                    'supports_recurrence' => false,
                    'default_all_day' => false,
                ],
            ],
            [
                'code' => 'final_exam',
                'name' => 'Final Exam',
                'description' => 'Final exam for a subject, course, or section.',
                'color' => '#b71c1c',
                'icon' => 'ri-award-line',
                'is_system' => true,
                'is_active' => true,
                'settings' => [
                    'category' => 'academic',
                    'requires_location' => true,
                    'requires_audience' => true,
                    'supports_attendance' => false,
                    'supports_response' => false,
                    'supports_recurrence' => false,
                    'default_all_day' => false,
                ],
            ],
            [
                'code' => 'quiz',
                'name' => 'Quiz',
                'description' => 'Short academic evaluation or quiz.',
                'color' => '#ef6c00',
                'icon' => 'ri-questionnaire-line',
                'is_system' => true,
                'is_active' => true,
                'settings' => [
                    'category' => 'academic',
                    'requires_location' => false,
                    'requires_audience' => true,
                    'supports_attendance' => false,
                    'supports_response' => false,
                    'supports_recurrence' => false,
                    'default_all_day' => false,
                ],
            ],
            [
                'code' => 'assignment_due',
                'name' => 'Assignment Due',
                'description' => 'Assignment, project, or homework due date.',
                'color' => '#5d4037',
                'icon' => 'ri-bookmark-3-line',
                'is_system' => true,
                'is_active' => true,
                'settings' => [
                    'category' => 'academic',
                    'requires_location' => false,
                    'requires_audience' => true,
                    'supports_attendance' => false,
                    'supports_response' => false,
                    'supports_recurrence' => false,
                    'default_all_day' => true,
                ],
            ],
            [
                'code' => 'grade_meeting',
                'name' => 'Grade Meeting',
                'description' => 'Meeting for a grade level, academic staff, or related team.',
                'color' => '#7b1fa2',
                'icon' => 'ri-team-line',
                'is_system' => true,
                'is_active' => true,
                'settings' => [
                    'category' => 'meeting',
                    'requires_location' => false,
                    'requires_audience' => true,
                    'supports_attendance' => true,
                    'supports_response' => true,
                    'supports_recurrence' => true,
                    'default_all_day' => false,
                ],
            ],
            [
                'code' => 'parent_meeting',
                'name' => 'Parent Meeting',
                'description' => 'Meeting with parents or guardians.',
                'color' => '#6a1b9a',
                'icon' => 'ri-group-line',
                'is_system' => true,
                'is_active' => true,
                'settings' => [
                    'category' => 'meeting',
                    'requires_location' => false,
                    'requires_audience' => true,
                    'supports_attendance' => true,
                    'supports_response' => true,
                    'supports_recurrence' => false,
                    'default_all_day' => false,
                ],
            ],
            [
                'code' => 'teacher_meeting',
                'name' => 'Teacher Meeting',
                'description' => 'Meeting for teachers, instructors, or academic coordinators.',
                'color' => '#512da8',
                'icon' => 'ri-user-star-line',
                'is_system' => true,
                'is_active' => true,
                'settings' => [
                    'category' => 'meeting',
                    'requires_location' => false,
                    'requires_audience' => true,
                    'supports_attendance' => true,
                    'supports_response' => true,
                    'supports_recurrence' => true,
                    'default_all_day' => false,
                ],
            ],
            [
                'code' => 'school_party',
                'name' => 'School Party',
                'description' => 'Celebration, school party, or institutional social event.',
                'color' => '#00897b',
                'icon' => 'ri-cake-3-line',
                'is_system' => true,
                'is_active' => true,
                'settings' => [
                    'category' => 'school_life',
                    'requires_location' => true,
                    'requires_audience' => true,
                    'supports_attendance' => true,
                    'supports_response' => true,
                    'supports_recurrence' => false,
                    'default_all_day' => false,
                ],
            ],
            [
                'code' => 'school_event',
                'name' => 'School Event',
                'description' => 'General institutional event, assembly, or school activity.',
                'color' => '#0097a7',
                'icon' => 'ri-calendar-event-line',
                'is_system' => true,
                'is_active' => true,
                'settings' => [
                    'category' => 'school_life',
                    'requires_location' => false,
                    'requires_audience' => true,
                    'supports_attendance' => true,
                    'supports_response' => false,
                    'supports_recurrence' => false,
                    'default_all_day' => false,
                ],
            ],
            [
                'code' => 'holiday',
                'name' => 'Holiday',
                'description' => 'Holiday, non-working day, or institutional closure.',
                'color' => '#388e3c',
                'icon' => 'ri-sun-line',
                'is_system' => true,
                'is_active' => true,
                'settings' => [
                    'category' => 'calendar',
                    'requires_location' => false,
                    'requires_audience' => false,
                    'supports_attendance' => false,
                    'supports_response' => false,
                    'supports_recurrence' => true,
                    'default_all_day' => true,
                ],
            ],
            [
                'code' => 'payment_due',
                'name' => 'Payment Due',
                'description' => 'Billing due date, tuition payment date, or financial deadline.',
                'color' => '#f9a825',
                'icon' => 'ri-money-dollar-circle-line',
                'is_system' => true,
                'is_active' => true,
                'settings' => [
                    'category' => 'billing',
                    'requires_location' => false,
                    'requires_audience' => true,
                    'supports_attendance' => false,
                    'supports_response' => false,
                    'supports_recurrence' => true,
                    'default_all_day' => true,
                ],
            ],
            [
                'code' => 'billing_reminder',
                'name' => 'Billing Reminder',
                'description' => 'Reminder related to invoices, payments, or financial obligations.',
                'color' => '#f57f17',
                'icon' => 'ri-alarm-warning-line',
                'is_system' => true,
                'is_active' => true,
                'settings' => [
                    'category' => 'billing',
                    'requires_location' => false,
                    'requires_audience' => true,
                    'supports_attendance' => false,
                    'supports_response' => false,
                    'supports_recurrence' => true,
                    'default_all_day' => true,
                ],
            ],
            [
                'code' => 'report_card_release',
                'name' => 'Report Card Release',
                'description' => 'Date for report card publication or grade release.',
                'color' => '#455a64',
                'icon' => 'ri-folder-chart-line',
                'is_system' => true,
                'is_active' => true,
                'settings' => [
                    'category' => 'academic',
                    'requires_location' => false,
                    'requires_audience' => true,
                    'supports_attendance' => false,
                    'supports_response' => false,
                    'supports_recurrence' => false,
                    'default_all_day' => true,
                ],
            ],
            [
                'code' => 'custom_event',
                'name' => 'Custom Event',
                'description' => 'Generic event type for any custom calendar need.',
                'color' => '#546e7a',
                'icon' => 'ri-edit-2-line',
                'is_system' => true,
                'is_active' => true,
                'settings' => [
                    'category' => 'General',
                    'requires_location' => false,
                    'requires_audience' => false,
                    'supports_attendance' => true,
                    'supports_response' => true,
                    'supports_recurrence' => true,
                    'default_all_day' => false,
                ],
            ],
        ];
    }
}
