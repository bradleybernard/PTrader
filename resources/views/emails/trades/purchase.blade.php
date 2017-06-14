@component('mail::message')
# Purchase Order

## Market
{{ $trade->market->name }} <img src="{{ $trade->market->image}}" style="width: 50px; height:50px;">

## Contract
{{ $trade->contract->long_name }} ({{ $trade->contract->ticker_symbol }})

## Trade:
Shares: {{ $trade->quantity }} <br/>
Share price: ${{ $trade->price_per_share }} <br/>
Share type: NO <br/>
Trade type: BUY <br/>
Total: ${{ $trade->total }} <br/>

## Account
Name: {{ $trade->account->name }} <br/>
Balance: ${{ $trade->account->available }}

@component('mail::button', ['url' => config('app.url') . '/accounts'])
View Accounts
@endcomponent

@component('mail::button', ['url' => config('app.url') . '/'])
View Markets
@endcomponent

{{ config('app.name') }}
@endcomponent
