@extends('layout/master')

@section('title', 'Accounts')
@section('content')
    <div class="container" style="padding-top: 40px; padding-bottom: 20px;">
        @each('include/account', $accounts, 'account')
    </div>
@endsection
