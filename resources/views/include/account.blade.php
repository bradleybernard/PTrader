<div class="row" style="margin-bottom: 20px;">
    <div class="col">
        <div class="card">
            <div class="card-header text-center">
                {{ $account->name }}
                @if($account->available >= 1.00) 
                    <span class="badge badge-success">Active</span>
                @else
                    <span class="badge badge-danger">Inactive</span>
                @endif
            </div>
            <div class="card-block">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th class="text-center">A_ID</th>
                                <th class="text-center">EMA</th>
                                <th class="text-center">TEL</th>
                                <th class="text-center">ALGO</th>
                                <th class="text-center">AVAIL</th>
                                <th class="text-center">G/L</th>
                                <th class="text-center">INV</th>
                                <th class="text-center">UPDATED</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="text-center">
                                <td class="text-center">
                                    <a href="/account/{{ $account->id }}/refresh"><span class="fa fa-refresh" style="cursor: pointer;"></span> {{ $account->id }}</a>
                                </td>
                                <td class="text-center">{{ substr($account->email, 0, 4) }}…</td>
                                <td class="text-center">{{ substr($account->phone, 0, 4) }}…</td>
                                <td class="text-center">{{ $account->algorithm }}</span></td>
                                <td class="text-center">
                                    @if($account->available >= 1.00)
                                        <span class="badge badge-success">${{ $account->available }}</span>
                                    @else 
                                        <span class="badge badge-danger">${{ $account->available }}</span>
                                    @endif
                                </td>
                                <td class="text-center">${{ $account->gain_loss }}</td>
                                <td class="text-center">${{ $account->invested }}</td>
                                <td class="text-center">{{ \Carbon\Carbon::parse($account->updated_at)->diffForHumans() }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                @if($account->trades->count() > 0)
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th class="text-center">T_ID</th>
                                <th class="text-center">M_ID</th>
                                <th class="text-center">C_ID</th>
                                <th class="text-center">ACTION</th>
                                <th class="text-center">TYPE</th>
                                <th class="text-center">QTY</th>
                                <th class="text-center">PPS</th>
                                <th class="text-center">TOTAL</th>
                                <th class="text-center">TIME</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($account->trades as $trade)
                                <tr>
                                    <td class="text-center">{{ $trade->id }}</td>
                                    <td class="text-center">{{ $trade->market_id }}</td>
                                    <td class="text-center">{{ $trade->contract_id }}</td>
                                    <td class="text-center">{{ $trade->action == 1 ? 'Buy' : 'Sell' }}</td>
                                    <td class="text-center">{{ $trade->type == 1 ? 'Yes' : 'No' }}</td>
                                    <td class="text-center">{{ $trade->quantity }}</td>
                                    <td class="text-center">${{ $trade->price_per_share }}</td>
                                    <td class="text-center">${{ $trade->total }}</td>
                                    <td class="text-center">{{ $trade->created_at }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
            <div class="card-footer text-muted text-center">
                Total Spent: ${{ $account->trades->sum('total') }}
            </div>
        </div>
    </div>
</div>
