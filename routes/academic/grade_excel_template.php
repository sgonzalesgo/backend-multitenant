<?php


use App\Http\Controllers\Academic\GradeExcelTemplateController;
use Illuminate\Support\Facades\Route;

Route::controller(GradeExcelTemplateController::class)->group(function () {
    Route::post(
        'grade-excel-templates/download',
        'download'
    )->middleware('permission:Download grade_excel_templates');
});
