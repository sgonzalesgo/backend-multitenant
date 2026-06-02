<?php

namespace App\Repositories\Academic\QualitativeEvaluation;

use App\Models\Academic\Course;
use App\Models\Academic\Enrollment;
use App\Models\Academic\Parallel;
use App\Models\Academic\QualitativeEvaluationComponent;
use App\Models\Academic\Subject;
use App\Models\Administration\Tenant;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class QualitativeExcelTemplateRepository
{
    protected function resolveCurrentTenantId(): ?string
    {
        if ($current = Tenant::current()) {
            return (string) $current->id;
        }

        $user = auth()->user();

        if (! $user || ! method_exists($user, 'token')) {
            return null;
        }

        return $user->token()?->tenant_id
            ? (string) $user->token()->tenant_id
            : null;
    }

    protected function requireTenantId(): string
    {
        $tenantId = $this->resolveCurrentTenantId();

        if (! $tenantId) {
            throw ValidationException::withMessages([
                'tenant' => __('messages.qualitative_excel_template.tenant_not_resolved'),
            ]);
        }

        return $tenantId;
    }

    public function generate(array $data): array
    {
        $tenantId = $this->requireTenantId();

        $course = Course::query()
            ->with('educationalLevel:id,code,name')
            ->where('id', Arr::get($data, 'course_id'))
            ->first();

        if (! $course) {
            throw ValidationException::withMessages([
                'course_id' => __('messages.qualitative_excel_template.course_not_found'),
            ]);
        }

        $parallel = Parallel::query()
            ->where('id', Arr::get($data, 'parallel_id'))
            ->first();

        if (! $parallel) {
            throw ValidationException::withMessages([
                'parallel_id' => __('messages.qualitative_excel_template.parallel_not_found'),
            ]);
        }

        $subject = Subject::query()
            ->where('id', Arr::get($data, 'subject_id'))
            ->first();

        if (! $subject) {
            throw ValidationException::withMessages([
                'subject_id' => __('messages.qualitative_excel_template.subject_not_found'),
            ]);
        }

        $templateFullPath = config('excel_grade_files.templates.grade_input_qualitative');

        if (! File::exists($templateFullPath)) {
            throw ValidationException::withMessages([
                'template' => __('messages.qualitative_excel_template.template_not_found'),
            ]);
        }

        $components = $this->getComponents($tenantId, $data);

        if ($components->isEmpty()) {
            throw ValidationException::withMessages([
                'components' => __('messages.qualitative_excel_template.components_not_generated'),
            ]);
        }

        $enrollments = $this->getEnrollments($tenantId, $data);

        if ($enrollments->isEmpty()) {
            throw ValidationException::withMessages([
                'students' => __('messages.qualitative_excel_template.students_not_found'),
            ]);
        }

        $spreadsheet = IOFactory::load($templateFullPath);

        $sourceSheetName = $this->resolveSourceSheetName($course);
        $sourceSheet = $spreadsheet->getSheetByName($sourceSheetName);

        if (! $sourceSheet) {
            throw ValidationException::withMessages([
                'template' => __('messages.qualitative_excel_template.sheet_not_found'),
            ]);
        }

        $this->removeUnusedSheets($spreadsheet, $sourceSheetName);

        foreach ($enrollments as $index => $enrollment) {
            $studentName = $this->resolveStudentName($enrollment);

            $sheet = $index === 0
                ? $sourceSheet
                : clone $sourceSheet;

            $sheet->setTitle($this->buildStudentSheetName($studentName, $index + 1));

            if ($index > 0) {
                $spreadsheet->addSheet($sheet);
            }

            $this->fillSheet(
                $sheet,
                $enrollment,
                $components,
                $course,
                $parallel,
                $subject
            );
        }

        $spreadsheet->setActiveSheetIndex(0);

        $outputDir = storage_path('app/generated/grades');

        if (! File::exists($outputDir)) {
            File::makeDirectory($outputDir, 0755, true);
        }

        $fileName = $this->buildFileName($course, $parallel, $subject);
        $outputPath = $outputDir.'/'.$fileName;

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->setPreCalculateFormulas(false);
        $writer->save($outputPath);

        $spreadsheet->disconnectWorksheets();

        return [
            'path' => $outputPath,
            'file_name' => $fileName,
            'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ];
    }

    protected function getComponents(string $tenantId, array $data): Collection
    {
        return QualitativeEvaluationComponent::query()
            ->with([
                'skillDefinition.area',
            ])
            ->where('tenant_id', $tenantId)
            ->where('academic_year_id', Arr::get($data, 'academic_year_id'))
            ->where('evaluation_period_id', Arr::get($data, 'evaluation_period_id'))
            ->where('course_id', Arr::get($data, 'course_id'))
            ->where('parallel_id', Arr::get($data, 'parallel_id'))
            ->where('modality_id', Arr::get($data, 'modality_id'))
            ->where('shift_id', Arr::get($data, 'shift_id'))
            ->where('subject_id', Arr::get($data, 'subject_id'))
            ->where('is_active', true)
            ->orderBy('order')
            ->get();
    }

    protected function getEnrollments(string $tenantId, array $data): Collection
    {
        return Enrollment::query()
            ->select('enrollments.*')
            ->join('students', 'students.id', '=', 'enrollments.student_id')
            ->join('persons', 'persons.id', '=', 'students.person_id')
            ->with([
                'student:id,person_id,student_code,status',
                'student.person:id,full_name,legal_id,email,photo',
                'enrollmentStatus:id,code,name',
                'academicYear:id,name',
                'shift:id,code,name',
            ])
            ->where('enrollments.tenant_id', $tenantId)
            ->where('enrollments.academic_year_id', Arr::get($data, 'academic_year_id'))
            ->where('enrollments.course_id', Arr::get($data, 'course_id'))
            ->where('enrollments.parallel_id', Arr::get($data, 'parallel_id'))
            ->where('enrollments.modality_id', Arr::get($data, 'modality_id'))
            ->where('enrollments.shift_id', Arr::get($data, 'shift_id'))
            ->when(
                Arr::get($data, 'specialty_id'),
                fn ($query, $value) => $query->where('enrollments.specialty_id', $value),
                fn ($query) => $query->whereNull('enrollments.specialty_id')
            )
            ->whereHas('enrollmentStatus', fn ($query) => $query->where('code', 'active'))
            ->orderByRaw('UPPER(persons.full_name) ASC')
            ->get();
    }

    protected function fillSheet(
        Worksheet $sheet,
        Enrollment $enrollment,
        Collection $components,
        Course $course,
        Parallel $parallel,
        Subject $subject
    ): void {
        $studentName = $this->resolveStudentName($enrollment);

        $sheet->setCellValue('B5', 'AÑO LECTIVO '.$this->resolveAcademicYearName($enrollment));
        $sheet->setCellValue('B7', 'Nombre: '.$studentName);
        $sheet->setCellValue('B9', 'Paralelo: "'.$parallel->name.'"');
        $sheet->setCellValue('O5', $subject->name);

        $startRow = $this->resolveStartRow($sheet);

        $this->clearComponentRows($sheet, $startRow);

        $groupedByArea = $components
            ->sortBy('order')
            ->groupBy(fn ($component) => $component->skillDefinition?->area?->id)
            ->values();

        $row = $startRow;
        $areaNumber = 1;

        foreach ($groupedByArea as $areaComponents) {
            $firstComponent = $areaComponents->first();
            $area = $firstComponent->skillDefinition?->area;

            if (! $area) {
                continue;
            }

            $this->copyRowStyle($sheet, $startRow, $row);

            $sheet->setCellValue("B{$row}", $areaNumber);
            $sheet->setCellValue("C{$row}", $area->name);

            $row++;

            foreach ($areaComponents as $component) {
                $skill = $component->skillDefinition;

                if (! $skill) {
                    continue;
                }

                $this->copyRowStyle($sheet, $startRow + 1, $row);

                $sheet->setCellValue("B{$row}", null);
                $sheet->setCellValue("C{$row}", $skill->name);

                foreach (range('D', 'O') as $column) {
                    if (! $sheet->getCell("{$column}{$row}")->isFormula()) {
                        $sheet->setCellValue("{$column}{$row}", null);
                    }
                }

                $sheet->getStyle("C{$row}")->getAlignment()->setWrapText(true);

                $row++;
            }

            $areaNumber++;
        }
    }

    protected function clearComponentRows(Worksheet $sheet, int $startRow): void
    {
        $highestRow = $sheet->getHighestRow();

        for ($row = $startRow; $row <= $highestRow; $row++) {
            foreach (range('B', 'O') as $column) {
                $cell = "{$column}{$row}";

                if (! $sheet->getCell($cell)->isFormula()) {
                    $sheet->setCellValue($cell, null);
                }
            }
        }
    }

    protected function copyRowStyle(Worksheet $sheet, int $sourceRow, int $targetRow): void
    {
        $sheet->duplicateStyle(
            $sheet->getStyle("B{$sourceRow}:O{$sourceRow}"),
            "B{$targetRow}:O{$targetRow}"
        );

        $sheet->getRowDimension($targetRow)
            ->setRowHeight($sheet->getRowDimension($sourceRow)->getRowHeight());
    }

    protected function resolveSourceSheetName(Course $course): string
    {
        $levelCode = strtoupper((string) $course->educationalLevel?->code);
        $courseName = strtoupper((string) $course->name);
        $courseCode = strtoupper((string) $course->code);
        $value = "{$courseName} {$courseCode}";

        if ($levelCode !== 'PR') {
            throw ValidationException::withMessages([
                'course_id' => __('messages.qualitative_excel_template.unsupported_educational_level'),
            ]);
        }

        if (Str::contains($value, ['PREPARATORIA', 'PREP'])) {
            return 'PREPARATORIA';
        }

        if (Str::contains($value, ['INICIAL 1', 'INICIAL-1', 'INICIAL1'])) {
            return 'INICIAL 1';
        }

        return 'INICIAL 2';
    }

    protected function resolveStartRow(Worksheet $sheet): int
    {
        return $sheet->getTitle() === 'PREPARATORIA'
            ? 16
            : 14;
    }

    protected function removeUnusedSheets(Spreadsheet $spreadsheet, string $sourceSheetName): void
    {
        for ($i = $spreadsheet->getSheetCount() - 1; $i >= 0; $i--) {
            $sheet = $spreadsheet->getSheet($i);

            if ($sheet->getTitle() !== $sourceSheetName) {
                $spreadsheet->removeSheetByIndex($i);
            }
        }
    }

    protected function resolveStudentName(Enrollment $enrollment): string
    {
        return $enrollment->student?->person?->full_name ?? 'ESTUDIANTE';
    }

    protected function resolveAcademicYearName(Enrollment $enrollment): string
    {
        return $enrollment->academicYear?->name ?? '';
    }

    protected function buildStudentSheetName(string $studentName, int $index): string
    {
        $clean = str_replace(['\\', '/', '*', '[', ']', ':', '?'], '-', $studentName);
        $name = trim($clean) !== '' ? $clean : "Estudiante {$index}";

        return mb_substr($index.' - '.$name, 0, 31);
    }

    protected function buildFileName(Course $course, Parallel $parallel, Subject $subject): string
    {
        $name = trim(
            ($course->name ?: $course->code).'_'.
            ($parallel->name ?: $parallel->code).'_'.
            ($subject->name ?: $subject->code)
        );

        $safe = str($name)
            ->replace([' ', '/', '\\', ':', '*', '?', '[', ']'], '_')
            ->lower()
            ->toString();

        return 'qualitative_grade_template_'.$safe.'_'.now()->format('Ymd_His').'.xlsx';
    }
}
