<div class="row">
    <div class="col-xs-12">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h2 class="panel-title" style="font-size: 25px;">{{ $market->name }} <span class="label label-danger label-sm">-{{ $market->deleted }}</span> <span class="label label-success">{{ $market->tweets_current - $market->tweets_start }}</span></h3>
            </div>
            <div class="panel-body">
                <dl class="dl-horizontal">
                    <dt>Market:</dt>
                    <dd><a href="{{ $market->url }}" target="_blank">{{ $market->ticker_symbol }}</a></dd>
                    <dt>Graphs: </dt>
                    <dd>
                        <a href="/market/{{ $market->market_id }}"><span class="glyphicon glyphicon-signal"></span></a> —
                        <a href="/sum/{{ $market->market_id }}"><span class="glyphicon glyphicon-plus"></span></a>
                    </dd>
                    <dt>From: </dt>
                    <dd>{{ $market->date_start }}</dd>
                    <dt>To: </dt>
                    <dd>{{ $market->date_end }}</dd>
                    <dt>Remaining: </dt>
                    <dd>{{ $market->remaining }} ({{ $market->minutes }} mins)</dd>
                </dl>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th class="text-center">ID</th>
                                <th class="text-center">Name</th>
                                <th class="text-center">BBYC</th>
                                <th class="text-center">BBNC</th>
                                <th class="text-center">BSYC</th>
                                <th class="text-center">BSNC</th>
                                <th class="text-center">LCP</th>
                                <th class="text-center">LTP</th>
                                <th class="text-center">Updated</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($market->contracts as $contract)
                                <tr class="text-center">
                                    <td>
                                        <a href="/contract/{{ $contract->contract_id }}"><span class="glyphicon glyphicon-signal"></span></a> — 
                                        <a href="{{ $contract->url }}" target="_blank">{{ $contract->contract_id }}</a>
                                    </td>
                                    <td>{{ $contract->short_name }}</td>
                                    <td>${{ $contract->history->best_buy_yes_cost }}</td>
                                    <td>${{ $contract->history->best_buy_no_cost }}</td>
                                    <td>${{ $contract->history->best_sell_yes_cost }}</td>
                                    <td>${{ $contract->history->best_sell_no_cost }}</td>
                                    <td>${{ $contract->history->last_close_price }}</td>
                                    <td>${{ $contract->history->last_trade_price }}</td>
                                    <td>{{ \Carbon\Carbon::parse($contract->history->created_at)->diffForHumans() }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
