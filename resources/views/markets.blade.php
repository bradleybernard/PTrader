@extends('layout/master')

@section('title', 'Markets')
@section('content')
    <div class="container" style="padding-top: 40px; padding-bottom: 20px;">
        @each('include/market', $markets, 'market')
    </div>
@endsection
