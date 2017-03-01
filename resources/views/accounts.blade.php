@extends('layout/master')

@section('title', 'Accounts')
@section('content')
    <div class="container" style="padding-top: 40px; padding-bottom: 20px;">
        <div class="text-center row" style="margin-bottom: 20px;">
            <div class="col-lg-12">
                <a href="/stats">Markets</a>
            </div>
        </div>
        @each('include/account', $accounts, 'account')
    </div>
@endsection
