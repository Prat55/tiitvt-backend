@extends('mail.layout.app')
@section('content')
    <h2>
        Your Requested Database Backup
    </h2>
    <table style="width:100%; border-collapse:collapse;">
        <tbody class="appointment-reminder-data">
            <p>
                <span class="fs-1 fw-semi-bold text-capitalize">
                    Hello,
                </span>
            </p>

            <p>
                Please find attached the requested database backup: <br>
                <strong>{{ $filename }}</strong>.
            </p>

            <p>
                <strong>This is an automated message. Do not reply to this email.</strong>
            </p>
        </tbody>
    </table>
    <br>
    Best regards,<br>
    {{ config('app.name') }} Team
@endsection
