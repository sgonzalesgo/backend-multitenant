@php
    $student = $enrollment->student;
    $studentPerson = $student?->person;

    $studentName = $studentPerson?->full_name ?? 'N/A';
    $studentEmail = $studentPerson?->email ?? 'N/A';
    $studentLegalId = $studentPerson?->legal_id ?? 'N/A';

    $studentCode = $student?->student_code ?? 'N/A';
    $enrollmentCode = $enrollment->enrollment_code ?? 'N/A';

    $academicYear = $enrollment->academicYear?->name ?? 'N/A';

    $course = $enrollment->course?->name ?? 'N/A';
    $parallel = $enrollment->parallel?->name ?? 'N/A';
    $shift = $enrollment->shift?->name ?? 'N/A';
    $status = $enrollment->enrollmentStatus?->name ?? 'N/A';

    $isRepresentative = $type === 'representative';
@endphp

    <!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('messages.enrollments.email_title') }}</title>
</head>

<body style="font-family: Arial, sans-serif; background-color: #f6f7fb; padding: 24px;">

<table width="100%" cellpadding="0" cellspacing="0"
       style="max-width: 720px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden;">

    <!-- HEADER -->
    <tr>
        <td style="padding: 24px; background: #1f2937; color: #ffffff;">
            <h2 style="margin: 0;">
                {{ __('messages.enrollments.email_title') }}
            </h2>
        </td>
    </tr>

    <!-- BODY -->
    <tr>
        <td style="padding: 24px; color: #222; line-height: 1.6;">

            <!-- GREETING -->
            <p>
                {{ __('messages.enrollments.email_greeting', [
                    'name' => $recipient->full_name ?? $recipient->name ?? ''
                ]) }}
            </p>

            <!-- INTRO -->
            @if ($isRepresentative)
                <p>
                    {{ __('messages.enrollments.representative_email_intro') }}
                </p>

                <p style="padding: 14px; background: #fff7ed; border-left: 4px solid #f97316;">
                    <strong>{{ __('messages.enrollments.important') }}:</strong>
                    {{ __('messages.enrollments.representative_linking_message') }}
                </p>
            @else
                <p>
                    {{ __('messages.enrollments.student_email_intro') }}
                </p>
            @endif

            <!-- TABLE -->
            <table width="100%" cellpadding="10" cellspacing="0"
                   style="border-collapse: collapse; margin-top: 20px;">

                <tr>
                    <td style="border: 1px solid #e5e7eb; font-weight: bold;">
                        {{ __('validation/Academic/enrollment.attributes.enrollment_code') }}
                    </td>
                    <td style="border: 1px solid #e5e7eb;">
                        <strong>{{ $enrollmentCode }}</strong>
                    </td>
                </tr>

                <tr>
                    <td style="border: 1px solid #e5e7eb; font-weight: bold;">
                        {{ __('messages.enrollments.student_code') }}
                    </td>
                    <td style="border: 1px solid #e5e7eb;">
                        <strong>{{ $studentCode }}</strong>
                    </td>
                </tr>

                <tr>
                    <td style="border: 1px solid #e5e7eb; font-weight: bold;">
                        {{ __('validation/Academic/enrollment.attributes.student_id') }}
                    </td>
                    <td style="border: 1px solid #e5e7eb;">
                        {{ $studentName }}
                    </td>
                </tr>

                <tr>
                    <td style="border: 1px solid #e5e7eb; font-weight: bold;">
                        Email
                    </td>
                    <td style="border: 1px solid #e5e7eb;">
                        {{ $studentEmail }}
                    </td>
                </tr>

                <tr>
                    <td style="border: 1px solid #e5e7eb; font-weight: bold;">
                        ID
                    </td>
                    <td style="border: 1px solid #e5e7eb;">
                        {{ $studentLegalId }}
                    </td>
                </tr>

                <tr>
                    <td style="border: 1px solid #e5e7eb; font-weight: bold;">
                        {{ __('validation/Academic/enrollment.attributes.academic_year_id') }}
                    </td>
                    <td style="border: 1px solid #e5e7eb;">
                        {{ $academicYear }}
                    </td>
                </tr>

                <tr>
                    <td style="border: 1px solid #e5e7eb; font-weight: bold;">
                        {{ __('validation/Academic/enrollment.attributes.course_id') }}
                    </td>
                    <td style="border: 1px solid #e5e7eb;">
                        {{ $course }}
                    </td>
                </tr>

                <tr>
                    <td style="border: 1px solid #e5e7eb; font-weight: bold;">
                        {{ __('validation/Academic/enrollment.attributes.parallel_id') }}
                    </td>
                    <td style="border: 1px solid #e5e7eb;">
                        {{ $parallel }}
                    </td>
                </tr>

                <tr>
                    <td style="border: 1px solid #e5e7eb; font-weight: bold;">
                        {{ __('validation/Academic/enrollment.attributes.shift_id') }}
                    </td>
                    <td style="border: 1px solid #e5e7eb;">
                        {{ $shift }}
                    </td>
                </tr>

                <tr>
                    <td style="border: 1px solid #e5e7eb; font-weight: bold;">
                        {{ __('validation/Academic/enrollment.attributes.enrollment_status_id') }}
                    </td>
                    <td style="border: 1px solid #e5e7eb;">
                        {{ $status }}
                    </td>
                </tr>

            </table>

            <!-- BOTÓN SOLO PARA REPRESENTANTES -->
            @if ($isRepresentative && !empty($registerUrl))
                <p style="margin-top: 30px; text-align: center;">
                    <a href="{{ $registerUrl }}"
                       style="display: inline-block; background: #2563eb; color: #ffffff; text-decoration: none; padding: 14px 24px; border-radius: 8px; font-weight: bold;">
                        {{ __('messages.enrollments.create_account_button') }}
                    </a>
                </p>
            @endif

            <!-- FOOTER -->
            <p style="margin-top: 30px;">
                {{ __('messages.enrollments.email_footer') }}
            </p>

        </td>
    </tr>

</table>

</body>
</html>
