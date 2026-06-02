<?php


use App\Http\Controllers\Academic\QualitativeEvaluation\QualitativeExcelTemplateController;
use Illuminate\Support\Facades\Route;

Route::controller(QualitativeExcelTemplateController::class)->group(function () {
    Route::post('qualitative-excel-templates/download', 'download')
        ->middleware('permission:Download qualitative_excel_templates');
});
