@extends('layout')

@section('content')
    <h1>{{ $greeting ?? 'Hello' }} {{ $name ?? ' User'}},</h1>
    <p>{{ $intro ?? 'Here is your OTP to proceed.' }}</p>
    <p><strong>{{ $otp ?? 'N/A'}}</strong></p>
    <p>{{ $outro ?? 'If you did not request this, please ignore this email.' }}</p>
@endsection

