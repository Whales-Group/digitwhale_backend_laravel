@extends('layout')

@section('content')
    <h1>{{ $greeting ?? 'Hello' }} {{ $name ?? 'User' }},</h1>
    <p>{{ $intro ?? 'Thank you for joining our community! We are excited to have you with us.' }}</p>
    <p>{{ $text ?? 'Feel free to explore and let us know if you have any questions or need assistance.' }}</p>
    <p>{{ $outro ?? 'We look forward to seeing you around!' }}</p>
    <p>Best Regards,<br>{{ $companyName ?? 'Our Team' }}</p>
@endsection
