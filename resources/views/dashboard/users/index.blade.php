@extends('layouts.app')

@section('title', 'User & Tenants Management')

@section('content')
    @include('dashboard.users.index-content')
@endsection

@section('page-script')
    <script src="{{ asset('js/modules/users.js') }}"></script>
@endsection