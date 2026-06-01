<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\GradeExcel\DownloadGradeExcelTemplateRequest;
use App\Repositories\Academic\GradeExcelTemplateRepository;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class GradeExcelTemplateController extends Controller
{
    public function __construct(
        protected GradeExcelTemplateRepository $repository
    ) {}

    public function download(
        DownloadGradeExcelTemplateRequest $request
    ): BinaryFileResponse {
        $file = $this->repository->generate($request->validated());

        return response()->download(
            $file['path'],
            $file['file_name'],
            [
                'Content-Type' => $file['mime_type'],
            ]
        )->deleteFileAfterSend(true);
    }
}
