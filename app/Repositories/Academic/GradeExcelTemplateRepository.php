<?php

namespace App\Repositories\Academic;

use App\Models\Academic\Course;
use App\Models\Academic\Enrollment;
use App\Models\Academic\GradeComponent;
use App\Models\Academic\Parallel;
use App\Models\Academic\Subject;
use App\Models\Administration\Tenant;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class GradeExcelTemplateRepository
{
    protected string $templatePath = 'templates/grades/grade_input_template.xlsx';

    protected int $studentStartRow = 4;

    protected string $studentNameColumn = 'A';

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

    /**
     * @throws ValidationException
     */
    protected function requireTenantId(): string
    {
        $tenantId = $this->resolveCurrentTenantId();

        if (! $tenantId) {
            throw ValidationException::withMessages([
                'tenant' => __('messages.grade_excel_template.tenant_not_resolved'),
            ]);
        }

        return $tenantId;
    }

    /**
     * @throws ValidationException
     */
    public function generate(array $data): array
    {
        $tenantId = $this->requireTenantId();

        $course = Course::query()
            ->with('educationalLevel:id,code,name')
            ->where('id', Arr::get($data, 'course_id'))
            ->first();

        if (! $course) {
            throw ValidationException::withMessages([
                'course_id' => __('messages.grade_excel_template.course_not_found'),
            ]);
        }

        $parallel = Parallel::query()
            ->where('id', Arr::get($data, 'parallel_id'))
            ->first();

        if (! $parallel) {
            throw ValidationException::withMessages([
                'parallel_id' => __('messages.grade_excel_template.parallel_not_found'),
            ]);
        }

        $subject = Subject::query()
            ->where('id', Arr::get($data, 'subject_id'))
            ->first();

        if (! $subject) {
            throw ValidationException::withMessages([
                'subject_id' => __('messages.grade_excel_template.subject_not_found'),
            ]);
        }

        $sourceSheetName = $this->resolveSourceSheetName($course);

        $templateFullPath = storage_path('app/'.$this->templatePath);

        if (! File::exists($templateFullPath)) {
            throw ValidationException::withMessages([
                'template' => __('messages.grade_excel_template.template_not_found'),
            ]);
        }

        $gradeComponents = $this->getGradeComponents($tenantId, $data);

        if ($gradeComponents->isEmpty()) {
            throw ValidationException::withMessages([
                'grade_components' => __('messages.grade_excel_template.components_not_generated'),
            ]);
        }

        $enrollments = $this->getEnrollments($tenantId, $data);

        if ($enrollments->isEmpty()) {
            throw ValidationException::withMessages([
                'students' => __('messages.grade_excel_template.students_not_found'),
            ]);
        }

        $spreadsheet = IOFactory::load($templateFullPath);

        $sourceSheet = $spreadsheet->getSheetByName($sourceSheetName);

        if (! $sourceSheet) {
            throw ValidationException::withMessages([
                'template' => __('messages.grade_excel_template.sheet_not_found'),
            ]);
        }

        for ($i = $spreadsheet->getSheetCount() - 1; $i >= 0; $i--) {
            $sheetItem = $spreadsheet->getSheet($i);

            if ($sheetItem->getTitle() !== $sourceSheetName) {
                $spreadsheet->removeSheetByIndex($i);
            }
        }

        $spreadsheet->setActiveSheetIndex(0);

        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setTitle(
            $this->buildFinalSheetName($course, $parallel, $subject)
        );

        $this->fillStudents($sheet, $enrollments);
        $this->resetStudentRows($sheet, $enrollments->count());
        $this->clearGradeInputCells($sheet, $gradeComponents, $enrollments->count());
        $this->clearUnusedStudentRows($sheet, $enrollments->count());

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

    /**
     * @throws ValidationException
     */
    protected function resolveSourceSheetName(Course $course): string
    {
        $levelCode = strtoupper((string) $course->educationalLevel?->code);

        return match ($levelCode) {
            'EBG' => '2DO EGB A 4TO EGB',
            'BGU' => '5TO EGB HASTA 3RO BACHILLERATO',
            'PR' => throw ValidationException::withMessages([
                'course_id' => __('messages.grade_excel_template.preschool_not_supported_yet'),
            ]),
            default => throw ValidationException::withMessages([
                'course_id' => __('messages.grade_excel_template.unsupported_educational_level'),
            ]),
        };
    }

    /**
     * @throws \Exception
     */
    protected function getGradeComponents(string $tenantId, array $data)
    {
        try {
            return GradeComponent::query()
                ->where('tenant_id', $tenantId)
                ->where('academic_year_id', Arr::get($data, 'academic_year_id'))
                ->where('evaluation_period_id', Arr::get($data, 'evaluation_period_id'))
                ->where('course_id', Arr::get($data, 'course_id'))
                ->where('parallel_id', Arr::get($data, 'parallel_id'))
                ->where('modality_id', Arr::get($data, 'modality_id'))
                ->where('shift_id', Arr::get($data, 'shift_id'))
                ->where('subject_id', Arr::get($data, 'subject_id'))
                ->when(
                    Arr::get($data, 'specialty_id'),
                    fn ($query, $value) => $query->where('specialty_id', $value),
                    fn ($query) => $query->whereNull('specialty_id')
                )
                ->where('is_active', true)
                ->orderBy('default_order')
                ->get();
        } catch (\Throwable $th) {
            throw new \Exception($th->getMessage());
        }

    }

    protected function getEnrollments(string $tenantId, array $data)
    {
        return Enrollment::query()
            ->select('enrollments.*')
            ->join('students', 'students.id', '=', 'enrollments.student_id')
            ->join('persons', 'persons.id', '=', 'students.person_id')
            ->with([
                'student:id,person_id,student_code,status',
                'student.person:id,full_name,legal_id,email,photo',
                'enrollmentStatus:id,code,name',
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

    protected function fillStudents($sheet, $enrollments): void
    {
        $row = $this->studentStartRow;

        foreach ($enrollments as $enrollment) {
            $studentName = $enrollment->student?->person?->full_name ?? '';

            $sheet->setCellValue(
                $this->studentNameColumn.$row,
                $studentName
            );

            $row++;
        }
    }

    protected function clearGradeInputCells($sheet, $gradeComponents, int $studentCount): void
    {
        $startRow = $this->studentStartRow;
        $endRow = $startRow + max($studentCount - 1, 0);

        /*
         * Limpiamos todas las celdas de entrada manual.
         * No tocamos fórmulas.
         *
         * BGU:
         * B:F  = formativo
         * H:L  = insumos formativos
         * N:O  = sumativo
         * X    = comportamiento
         *
         * EBG:
         * B:F  = formativo
         * U    = comportamiento
         */
        $inputColumns = [
            'B', 'C', 'D', 'E', 'F',
            'H', 'I', 'J', 'K', 'L',
            'N', 'O',
            'U', 'X',
        ];

        for ($row = $startRow; $row <= $endRow; $row++) {
            foreach ($inputColumns as $column) {
                $cell = $column.$row;

                if (! $sheet->getCell($cell)->isFormula()) {
                    $sheet->setCellValue($cell, null);
                }
            }
        }
    }

    protected function buildFinalSheetName(Course $course, Parallel $parallel, Subject $subject): string {
        $courseName = $course->name ?: $course->code;
        $parallelName = $parallel->name ?: $parallel->code;
        $subjectName = $subject->name ?: $subject->code;

        $name = trim($courseName.' - '.$parallelName.' - '.$subjectName);

        $name = str_replace(['\\', '/', '*', '[', ']', ':', '?'], '-', $name);

        return mb_substr($name, 0, 31);
    }

    protected function buildFileName(Course $course, Parallel $parallel, Subject $subject): string {
        $name = $this->buildFinalSheetName($course, $parallel, $subject);

        $safe = str($name)
            ->replace([' ', '/', '\\', ':', '*', '?', '[', ']'], '_')
            ->lower()
            ->toString();

        return 'grade_template_'.$safe.'_'.now()->format('Ymd_His').'.xlsx';
    }

    protected function clearUnusedStudentRows($sheet, int $studentCount): void
    {
        $templateStudentRows = 25;

        $firstUnusedRow = $this->studentStartRow + $studentCount;
        $lastTemplateRow = $this->studentStartRow + $templateStudentRows - 1;

        if ($firstUnusedRow > $lastTemplateRow) {
            return;
        }

        $rowsToRemove = $lastTemplateRow - $firstUnusedRow + 1;

        $sheet->removeRow($firstUnusedRow, $rowsToRemove);
    }

    protected function resetStudentRows($sheet, int $studentCount): void
    {
        $startRow = $this->studentStartRow;
        $endRow = $startRow + max($studentCount - 1, 0);

        $highestColumn = $sheet->getHighestColumn();
        $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);

        // Empezamos en B para no borrar el nombre del estudiante en A
        $startColumnIndex = Coordinate::columnIndexFromString('B');

        for ($row = $startRow; $row <= $endRow; $row++) {
            for ($columnIndex = $startColumnIndex; $columnIndex <= $highestColumnIndex; $columnIndex++) {
                $column = Coordinate::stringFromColumnIndex($columnIndex);
                $cell = $column.$row;

                if (! $sheet->getCell($cell)->isFormula()) {
                    $sheet->setCellValue($cell, null);
                }
            }
        }
    }
}
