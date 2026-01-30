@extends('layouts.app')

@section('title', 'Logs Management')

@section('content')
    @include('dashboard.logs.index-content')
@endsection

@section('page-script')
    <script src="{{ asset('js/modules/logs.js') }}?v=1.0"></script>
@endsection