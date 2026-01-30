@extends('layouts.app')

@section('title', 'RVM Machines Management')

@section('content')
    @include('dashboard.machines.index-content')
@endsection

@section('page-script')
    <script src="{{ asset('js/modules/machines.js') }}?v={{ filemtime(public_path('js/modules/machines.js')) }}"></script>
@endsection