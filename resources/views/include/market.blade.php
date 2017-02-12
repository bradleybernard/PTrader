<div class="row">
    <div class="col-xs-12">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h2 class="panel-title" style="font-size: 25px;">{{ $market->name }} <span class="label label-danger label-sm">-{{ $market->deleted }}</span> <span class="label label-success">{{ $market->tweets_current - $market->tweets_start }}</span></h3>
            </div>
            <div class="panel-body">
                <dl class="dl-horizontal">
                    <dt>PredictIt Market:</dt>
                    <dd><a href="{{ $market->url }}" target="_blank">{{ $market->ticker_symbol }}</a></dd>
                    <dt>Twitter:</dt>
                    <dd><a href="https://twitter.com/{{ $market->twitter->username }}" target="_blank">{{ '@' . $market->twitter->username }}</a></dd>
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
                    <table class="table table-bordered table-hover table-striped">
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
                                    <td style="
                                    @if(($market->tweets_current - $market->tweets_start) >= $contract->MinTweets && ($market->tweets_current - $market->tweets_start) <= $contract->MaxTweets)
                                        background-color: gold;
                                    @endif
                                    ">{{ $contract->short_name }}</td>

                                    @foreach($columns as $column)
                                        <td style="{{ isset($market->maxes[$column][$contract->contract_id]) ? 'background-color: rgba(255, 0, 0, 0.5);' : '' }} {{ isset($market->mins[$column][$contract->contract_id]) ? 'background-color: rgba(0, 255, 0, 0.5);' : '' }}" >${{ $contract->history->{$column} == 0 || $contract->history->{$column} == 1.00 ? 'None' : $contract->history->{$column} }}</td>
                                    @endforeach

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
