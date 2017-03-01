@extends('layout/master')

@section('title', 'Markets')
@section('content')

    <div class="container" style="padding-top: 40px; padding-bottom: 20px;">
        <div class="text-center row" style="margin-bottom: 20px;">
            <div class="col-lg-12">
                <a href="/accounts">Accounts</a>
            </div>
        </div>
        @each('include/market', $markets, 'market')
    </div>
@endsection
