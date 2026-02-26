@extends('mail.layout.app')

@section('content')
    <p class="subject">Happy Birthday, {{ $student->first_name }}!</p>

    <p>Dear {{ $student->full_name }},</p>

    <p>
        Wishing you a very happy birthday from all of us at {{ config('app.name') }}.
        May your day be filled with happiness, success, and great moments.
    </p>

    <p>Have a wonderful year ahead.</p>

    <p>Best regards,<br>{{ config('app.name') }} Team</p>
@endsection
