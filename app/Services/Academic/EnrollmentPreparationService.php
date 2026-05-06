<?php

namespace App\Services\Academic;

use App\Models\Academic\Course;
use App\Models\Academic\Enrollment;
use App\Models\Academic\Student;
use App\Models\Administration\Tenant;
use App\Models\General\Person;
use App\Services\General\PersonLookupService;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class EnrollmentPreparationService
{
    public function __construct(
        protected PersonLookupService $personLookupService,
        protected StudentPromotionService $studentPromotionService
    ) {}

    /**
     * @throws ValidationException
     */
    public function prepareByLegalId(string $legalId): array
    {
        $tenantId = $this->resolveCurrentTenantId();

        if (! $tenantId) {
            throw ValidationException::withMessages([
                'tenant' => __('messages.enrollments.tenant_not_resolved'),
            ]);
        }

        $lookup = $this->personLookupService->lookup($legalId);

        if (empty($lookup['person'])) {
            return $this->emptyResponse($lookup['source'] ?? null);
        }

        if (($lookup['source'] ?? null) !== 'database') {
            return [
                'source' => $lookup['source'],
                'person' => $lookup['person'],
                'student' => null,
                'last_enrollment' => null,
                'representatives' => [],
                'promotion_suggestion' => null,
                'suggested_course' => null,
            ];
        }

        $personId = Arr::get($lookup, 'person.id');

        $person = Person::query()
            ->with([
                'user:id,person_id,name,email,avatar,status',
                'country:id,code,name',
                'state:id,country_id,code,name',
                'city:id,state_id,name',
            ])
            ->where('id', $personId)
            ->first();

        if (! $person) {
            return $this->emptyResponse('database');
        }

        $student = Student::query()
            ->with([
                'person:id,full_name,email,phone,legal_id,legal_id_type,photo,birthday,gender,address,country_id,state_id,city_id,zip',
                'legalRepresentativeRelationships.legalRepresentative.person:id,full_name,email,phone,legal_id,legal_id_type,photo',
            ])
            ->where('tenant_id', $tenantId)
            ->where('person_id', $person->id)
            ->first();

        if (! $student) {
            return [
                'source' => 'database',
                'person' => $person,
                'student' => null,
                'last_enrollment' => null,
                'representatives' => [],
                'promotion_suggestion' => null,
                'suggested_course' => null,
            ];
        }

        $lastEnrollment = $this->getLastEnrollment($student, $tenantId);

        $promotionSuggestion = null;
        $suggestedCourse = null;

        if (
            $lastEnrollment &&
            $lastEnrollment->course &&
            $lastEnrollment->course->educationalLevel &&
            $lastEnrollment->course->level_number
        ) {
            $promotionSuggestion = $this->studentPromotionService->getNextLevelAndNumber(
                $lastEnrollment->course->educationalLevel,
                (int) $lastEnrollment->course->level_number
            );

            $suggestedCourse = $this->findSuggestedCourse($promotionSuggestion, $tenantId);
        }

        return [
            'source' => 'database',
            'person' => $person,
            'student' => $student,
            'last_enrollment' => $lastEnrollment,
            'representatives' => $this->formatRepresentatives($student),
            'promotion_suggestion' => $promotionSuggestion,
            'suggested_course' => $suggestedCourse,
        ];
    }

    protected function getLastEnrollment(Student $student, string $tenantId): ?Enrollment
    {
        return Enrollment::query()
            ->with([
                'academicYear:id,name',
                'course:id,tenant_id,educational_level_id,code,name,level_number,status',
                'course.educationalLevel:id,tenant_id,code,name,start_number,end_number,next_educational_level_id',
                'course.educationalLevel.nextEducationalLevel:id,tenant_id,code,name,start_number,end_number',
                'parallel:id,name',
                'shift:id,name',
                'enrollmentStatus:id,name',
                'assignedUser:id,person_id,name,email,status',
            ])
            ->where('tenant_id', $tenantId)
            ->where('student_id', $student->id)
            ->orderByDesc('submitted_at')
            ->orderByDesc('created_at')
            ->first();
    }

    protected function findSuggestedCourse(?array $promotionSuggestion, string $tenantId): ?Course
    {
        if (
            ! $promotionSuggestion ||
            ! empty($promotionSuggestion['is_graduated']) ||
            empty($promotionSuggestion['next_level_id']) ||
            empty($promotionSuggestion['next_number'])
        ) {
            return null;
        }

        return Course::query()
            ->with([
                'educationalLevel:id,tenant_id,code,name,start_number,end_number,next_educational_level_id',
            ])
            ->where('tenant_id', $tenantId)
            ->where('educational_level_id', $promotionSuggestion['next_level_id'])
            ->where('level_number', $promotionSuggestion['next_number'])
            ->where('status', 'active')
            ->orderBy('name')
            ->first();
    }

    protected function formatRepresentatives(Student $student): array
    {
        return $student->legalRepresentativeRelationships
            ->map(function ($relationship) {
                return [
                    'id' => $relationship->id,
                    'legal_representative_id' => $relationship->legal_representative_id,
                    'relationship_type' => $relationship->relationship_type,
                    'description' => $relationship->description,
                    'is_billable' => (bool) $relationship->is_billable,
                    'is_emergency_contact' => (bool) $relationship->is_emergency_contact,
                    'legal_representative' => $relationship->legalRepresentative,
                ];
            })
            ->values()
            ->all();
    }

    protected function emptyResponse(?string $source = null): array
    {
        return [
            'source' => $source,
            'person' => null,
            'student' => null,
            'last_enrollment' => null,
            'representatives' => [],
            'promotion_suggestion' => null,
            'suggested_course' => null,
        ];
    }

    protected function resolveCurrentTenantId(): ?string
    {
        if ($current = Tenant::current()) {
            return (string) $current->id;
        }

        $user = auth()->user();

        if (! $user || ! method_exists($user, 'token')) {
            return null;
        }

        $token = $user->token();

        if (! $token || empty($token->tenant_id)) {
            return null;
        }

        return (string) $token->tenant_id;
    }
}
