<?php

namespace App\Services\Academic;

use App\Models\Academic\Course;
use App\Models\Academic\Enrollment;
use App\Models\Academic\LegalRepresentative;
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
            return $this->response(source: $lookup['source'] ?? null);
        }

        if (($lookup['source'] ?? null) !== 'database') {
            return $this->response(
                source: $lookup['source'],
                person: $lookup['person'],
                found: [
                    'external_person' => true,
                ]
            );
        }

        $person = $this->getPerson(Arr::get($lookup, 'person.id'));

        if (! $person) {
            return $this->response(source: 'database');
        }

        $student = $this->getStudent($person, $tenantId);
        $legalRepresentative = $this->getLegalRepresentative($person, $tenantId);

        $lastEnrollment = null;
        $representatives = [];
        $promotionSuggestion = null;
        $suggestedCourse = null;

        if ($student) {
            $lastEnrollment = $this->getLastEnrollment($student, $tenantId);
            $representatives = $this->formatRepresentatives($student);

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

                $suggestedCourse = $this->findSuggestedCourse(
                    $promotionSuggestion,
                    $tenantId,
                    $lastEnrollment->course?->id
                );

                $promotionSuggestion = $this->formatPromotionSuggestion(
                    $promotionSuggestion,
                    $lastEnrollment,
                    $suggestedCourse
                );
            }
        }

        return $this->response(
            source: 'database',
            person: $person,
            student: $student,
            legalRepresentative: $legalRepresentative,
            lastEnrollment: $lastEnrollment,
            representatives: $representatives,
            promotionSuggestion: $promotionSuggestion,
            suggestedCourse: $suggestedCourse,
            found: [
                'person' => true,
                'student' => (bool) $student,
                'legal_representative' => (bool) $legalRepresentative,
                'previous_enrollment' => (bool) $lastEnrollment,
            ]
        );
    }

    protected function response(
        ?string $source = null,
        mixed $person = null,
        mixed $student = null,
        mixed $legalRepresentative = null,
        mixed $lastEnrollment = null,
        array $representatives = [],
        mixed $promotionSuggestion = null,
        mixed $suggestedCourse = null,
        array $found = []
    ): array {
        return [
            'source' => $source,

            'found' => array_merge([
                'person' => false,
                'student' => false,
                'legal_representative' => false,
                'previous_enrollment' => false,
                'external_person' => false,
            ], $found),

            'person' => $person,
            'student' => $student,
            'legal_representative' => $legalRepresentative,
            'last_enrollment' => $lastEnrollment,
            'representatives' => $representatives,
            'promotion_suggestion' => $promotionSuggestion,
            'suggested_course' => $suggestedCourse,
        ];
    }

    protected function getPerson(?string $personId): ?Person
    {
        if (! $personId) {
            return null;
        }

        return Person::query()
            ->select([
                'id',
                'full_name',
                'email',
                'phone',
                'legal_id',
                'legal_id_type',
                'photo',
                'birthday',
                'gender',
                'address',
                'country_id',
                'state_id',
                'city_id',
                'zip',
                'marital_status',
                'blood_group',
                'nationality',
                'deceased_at',
                'status_changed_at',
            ])
            ->with([
                'user:id,person_id,name,email,avatar,status',
                'country:id,code,name',
                'state:id,country_id,code,name',
                'city:id,state_id,name',
            ])
            ->where('id', $personId)
            ->first();
    }

    protected function getStudent(Person $person, string $tenantId): ?Student
    {
        return Student::query()
            ->with([
                'person:id,full_name,email,phone,legal_id,legal_id_type,photo,birthday,gender,address,country_id,state_id,city_id,zip,marital_status,blood_group,nationality,deceased_at,status_changed_at',
                'person.country:id,code,name',
                'person.state:id,country_id,code,name',
                'person.city:id,state_id,name',

                'legalRepresentativeRelationships:id,tenant_id,student_id,legal_representative_id,relationship_type,description,is_billable,is_emergency_contact',
                'legalRepresentativeRelationships.legalRepresentative:id,tenant_id,person_id,status,notes',
                'legalRepresentativeRelationships.legalRepresentative.person:id,full_name,email,phone,legal_id,legal_id_type,photo,birthday,gender,address,country_id,state_id,city_id,zip,marital_status,blood_group,nationality,deceased_at,status_changed_at',
                'legalRepresentativeRelationships.legalRepresentative.person.country:id,code,name',
                'legalRepresentativeRelationships.legalRepresentative.person.state:id,country_id,code,name',
                'legalRepresentativeRelationships.legalRepresentative.person.city:id,state_id,name',
            ])
            ->where('tenant_id', $tenantId)
            ->where('person_id', $person->id)
            ->first();
    }

    protected function getLegalRepresentative(Person $person, string $tenantId): ?LegalRepresentative
    {
        return LegalRepresentative::query()
            ->with([
                'person:id,full_name,email,phone,legal_id,legal_id_type,photo,birthday,gender,address,country_id,state_id,city_id,zip,marital_status,blood_group,nationality,deceased_at,status_changed_at',
                'person.country:id,code,name',
                'person.state:id,country_id,code,name',
                'person.city:id,state_id,name',

                'studentRelationships:id,tenant_id,student_id,legal_representative_id,relationship_type,description,is_billable,is_emergency_contact',
                'studentRelationships.student:id,tenant_id,person_id,student_code,status,notes',
                'studentRelationships.student.person:id,full_name,email,phone,legal_id,legal_id_type,photo,birthday,gender,address,country_id,state_id,city_id,zip',
            ])
            ->where('tenant_id', $tenantId)
            ->where('person_id', $person->id)
            ->first();
    }

    protected function getLastEnrollment(Student $student, string $tenantId): ?Enrollment
    {
        return Enrollment::query()
            ->with([
                'tenant:id,name',

                'student:id,tenant_id,person_id,student_code,status,notes',
                'student.person:id,full_name,email,phone,legal_id,legal_id_type,photo,birthday,gender,address,country_id,state_id,city_id,zip,marital_status,blood_group,nationality,deceased_at,status_changed_at',
                'student.person.country:id,code,name',
                'student.person.state:id,country_id,code,name',
                'student.person.city:id,state_id,name',

                'academicYear:id,name',
                'course:id,tenant_id,educational_level_id,code,name,level_number,status',
                'course.educationalLevel:id,tenant_id,code,name,has_specialty,start_number,end_number,next_educational_level_id',
                'course.educationalLevel.specialties:id,tenant_id,code,name,description,is_active',
                'course.educationalLevel.nextEducationalLevel:id,tenant_id,code,name,start_number,end_number',
                'parallel:id,name',
                'shift:id,name',
                'enrollmentStatus:id,name',
                'assignedUser:id,person_id,name,email,status',
                'assignedUser.person:id,full_name,email,phone,legal_id,legal_id_type,photo',
            ])
            ->where('tenant_id', $tenantId)
            ->where('student_id', $student->id)
            ->orderByDesc('submitted_at')
            ->orderByDesc('created_at')
            ->first();
    }

    protected function findSuggestedCourse(
        ?array $promotionSuggestion,
        string $tenantId,
        ?string $currentCourseId = null
    ): ?Course {
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
                'educationalLevel:id,tenant_id,code,name,has_specialty,start_number,end_number,next_educational_level_id',
                'educationalLevel.specialties:id,tenant_id,code,name,description,is_active',
            ])
            ->where('tenant_id', $tenantId)
            ->where('educational_level_id', $promotionSuggestion['next_level_id'])
            ->where('level_number', $promotionSuggestion['next_number'])
            ->where('status', 'active')
            ->when($currentCourseId, fn ($query) => $query->where('id', '!=', $currentCourseId))
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

    protected function formatPromotionSuggestion(
        ?array $promotionSuggestion,
        Enrollment $lastEnrollment,
        ?Course $suggestedCourse
    ): ?array {
        if (! $promotionSuggestion) {
            return null;
        }

        $currentLevel = $lastEnrollment->course?->educationalLevel;

        $nextLevel = $promotionSuggestion['is_graduated']
            ? null
            : (
            ($promotionSuggestion['next_level_id'] ?? null) === ($currentLevel?->id ?? null)
                ? $currentLevel
                : $currentLevel?->nextEducationalLevel
            );

        $currentLevelData = $currentLevel ? [
            'id' => $currentLevel->id,
            'code' => $currentLevel->code,
            'name' => $currentLevel->name,
            'start_number' => $currentLevel->start_number,
            'end_number' => $currentLevel->end_number,
        ] : null;

        $nextLevelData = $nextLevel ? [
            'id' => $nextLevel->id,
            'code' => $nextLevel->code,
            'name' => $nextLevel->name,
            'start_number' => $nextLevel->start_number,
            'end_number' => $nextLevel->end_number,
        ] : null;

        $label = null;

        if (! empty($promotionSuggestion['is_graduated'])) {
            $label = __('messages.enrollments.student_graduated');
        } elseif ($suggestedCourse) {
            $label = $suggestedCourse->name;
        } elseif ($nextLevel) {
            $label = trim($nextLevel->name.' '.($promotionSuggestion['next_number'] ?? ''));
        } elseif (! empty($promotionSuggestion['next_number'])) {
            $label = __('messages.enrollments.level_number', [
                'number' => $promotionSuggestion['next_number'],
            ]);
        }

        return array_merge($promotionSuggestion, [
            'label' => $label,
            'current_level' => $currentLevelData,
            'next_level' => $nextLevelData,
            'suggested_course' => $suggestedCourse,
        ]);
    }
}

