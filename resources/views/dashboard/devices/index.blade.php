@extends('layouts.app')

@section('title', 'Edge Devices Management')

@section('content')
    @include('dashboard.devices.index-content')
@endsection

@section('page-script')
    <script src="{{ asset('js/modules/devices.js') }}"></script>
@endsection