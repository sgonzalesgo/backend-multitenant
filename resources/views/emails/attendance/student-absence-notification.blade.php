<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{ __('messages.attendance.absence_email_subject') }}</title>
</head>
<body style="margin:0; padding:0; background-color:#f4f6f8; font-family:Arial, sans-serif; color:#333;">
<table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f6f8; padding:30px 0;">
    <tr>
        <td align="center">
            <table width="650" cellpadding="0" cellspacing="0" style="background:#ffffff; border-radius:8px; overflow:hidden;">
                <tr>
                    <td style="background:#4f46e5; color:#ffffff; padding:20px 30px; font-size:20px; font-weight:bold;">
                        Eduolivo
                    </td>
                </tr>

                <tr>
                    <td style="padding:30px;">
                        <h2 style="margin-top:0; color:#111827;">
                            {{ __('messages.attendance.absence_email_greeting', ['name' => $representativeName]) }}
                        </h2>

                        <p style="font-size:15px; line-height:1.6;">
                            {{ __('messages.attendance.absence_email_intro') }}
                        </p>

                        <div style="background:#f9fafb; border:1px solid #e5e7eb; border-radius:6px; padding:15px; margin:20px 0;">
                            <p style="margin:0 0 8px;">
                                <strong>{{ __('messages.attendance.student') }}:</strong>
                                {{ $student->person?->full_name }}
                            </p>

                            <p style="margin:0;">
                                <strong>{{ __('messages.attendance.attendance_date') }}:</strong>
                                {{ now()->format('Y-m-d') }}
                            </p>
                        </div>

                        <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse; margin-top:20px;">
                            <thead>
                            <tr>
                                <th align="left" style="border-bottom:2px solid #e5e7eb; padding:10px; font-size:14px;">
                                    {{ __('messages.attendance.course') }}
                                </th>

                                <th align="left" style="border-bottom:2px solid #e5e7eb; padding:10px; font-size:14px;">
                                    {{ __('messages.attendance.subject') }}
                                </th>

                                <th align="left" style="border-bottom:2px solid #e5e7eb; padding:10px; font-size:14px;">
                                    {{ __('messages.attendance.instructor') }}
                                </th>

                                <th align="left" style="border-bottom:2px solid #e5e7eb; padding:10px; font-size:14px;">
                                    {{ __('messages.attendance.observation') }}
                                </th>
                            </tr>
                            </thead>

                            <tbody>
                            @foreach ($absenceRecords as $record)
                                <tr>
                                    <td style="border-bottom:1px solid #e5e7eb; padding:10px; font-size:14px;">
                                        {{ $record->attendanceSession?->course?->name ?? '-' }}
                                    </td>

                                    <td style="border-bottom:1px solid #e5e7eb; padding:10px; font-size:14px;">
                                        {{ $record->attendanceSession?->subject?->name ?? '-' }}
                                    </td>

                                    <td style="border-bottom:1px solid #e5e7eb; padding:10px; font-size:14px;">
                                        {{ $record->attendanceSession?->instructor?->person?->full_name ?? '-' }}
                                    </td>

                                    <td style="border-bottom:1px solid #e5e7eb; padding:10px; font-size:14px;">
                                        {{ $record->observation ?? '-' }}
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>

                        <p style="font-size:15px; line-height:1.6; margin-top:25px;">
                            {{ __('messages.attendance.absence_email_footer') }}
                        </p>

                        <p style="font-size:15px; margin-top:25px;">
                            {{ __('messages.attendance.absence_email_regards') }}
                        </p>
                    </td>
                </tr>

                <tr>
                    <td align="center" style="background:#f9fafb; color:#9ca3af; padding:15px; font-size:12px;">
                        © {{ date('Y') }} Eduolivo.
                        {{ __('messages.attendance.all_rights_reserved') }}
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
