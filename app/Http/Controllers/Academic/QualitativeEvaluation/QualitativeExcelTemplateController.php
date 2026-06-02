<?php

namespace App\Http\Controllers\Academic\QualitativeEvaluation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\QualitativeExcelTemplate\DownloadQualitativeExcelTemplateRequest;

use App\Repositories\Academic\QualitativeEvaluation\QualitativeExcelTemplateRepository;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class QualitativeExcelTemplateController extends Controller
{
    public function __construct(
        protected QualitativeExcelTemplateRepository $repository
    ) {}

    public function download(DownloadQualitativeExcelTemplateRequest $request): BinaryFileResponse
    {
        $file = $this->repository->generate($request->validated());

        return response()
            ->download(
                $file['path'],
                $file['file_name'],
                [
                    'Content-Type' => $file['mime_type'],
                ]
            )
            ->deleteFileAfterSend(true);
    }
}
