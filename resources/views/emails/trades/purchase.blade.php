@component('mail::message')
# Purchase Orders ({{ count($trades) }})

## Market
{{ $trades[0]->market->name }} <img src="{{ $trades[0]->market->image}}" style="width: 50px; height:50px;">

@component('mail::table')
| Twitter | Contract | Qty | Price | Total |
|:-------:|:--------:|:---:|:-----:|:-----:|
@foreach($trades as $trade)
| {{ $trade->twitter->username }} | {{ $trade->contract->short_name }} | {{ $trade->quantity }} | {{ $trade->price_per_share }} | {{ $trade->total }} |
@endforeach
@endcomponent

## Account
Name: {{ $trades[0]->account->name }} <br/>
Balance: ${{ $trades[0]->account->available }}

@component('mail::button', ['url' => config('app.url') . '/accounts'])
View Accounts
@endcomponent

@component('mail::button', ['url' => config('app.url') . '/'])
View Markets
@endcomponent

{{ config('app.name') }}
@endcomponent
