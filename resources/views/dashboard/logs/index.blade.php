@extends('layouts.app')

@section('title', 'Logs Management')

@section('page-style')
    <link rel="stylesheet" href="/vendor/assets/vendor/libs/sweetalert2/sweetalert2.css" />
@endsection

@section('content')
    @include('dashboard.logs.index-content')
@endsection

@section('page-script')
    <script src="/vendor/assets/vendor/libs/sweetalert2/sweetalert2.js"></script>
    <script src="{{ asset('js/modules/logs.js') }}?v=1.1"></script>
@endsection