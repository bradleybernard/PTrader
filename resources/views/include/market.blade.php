<div class="row" style="margin-bottom: 20px;">
    <div class="col">
        <div class="card">
            <div class="card-header text-center">
                {{ $market->name }}
                @if($market->active) 
                    <span class="badge badge-success">Open</span>
                @else
                    <span class="badge badge-danger">Closed</span>
                @endif
            </div>
            <div class="card-block">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                               {{--  <th class="text-center">G</th> --}}
                                <th class="text-center">M_ID</th>
                                <th class="text-center">Twit</th>
                                <th class="text-center">Cnt</th>
                                <th class="text-center">Del</th>
                                <th class="text-center">Start</th>
                                <th class="text-center">Curr</th>
                                <th class="text-center">EndDate</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="text-center">
                               {{--  <td>
                                    <a href="/market/{{ $market->market_id }}"><span class="fa fa-area-chart" style="cursor: pointer;"></span></a> &nbsp;&nbsp; 
                                    <a href="/sum/{{ $market->market_id }}"><span class="fa fa-line-chart" style="cursor: pointer;"></span></a>
                                </td> --}}
                                <td><a href="{{ $market->url }}" target="_blank">{{ $market->market_id }}</a></td>
                                <td><a href="https://twitter.com/{{ $market->twitter->username }}" target="_blank">{{ '@' . substr($market->twitter->username, 0, 2) }}</a></td>
                                <td><span class="badge badge-success">{{ $market->tweets_current - $market->tweets_start }}</span></td>
                                <td><span class="badge badge-danger">{{ $market->deleted }}</span></td>
                                <td>{{ $market->tweets_start }}</td>
                                <td>{{ $market->tweets_current }}</td>
                                <td>
                                    {{ $market->remaining }}
                                    @if($market->minutes)
                                        ({{ $market->minutes }}m)
                                    @endif
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                {{-- <th class="text-center">G</th> --}}
                                <th class="text-center">C_ID</th>
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
                                    {{-- <td><a href="/contract/{{ $contract->contract_id }}"><span class="fa fa-area-chart" style="cursor: pointer;"></span></a></td> --}}
                                    <td><a href="{{ $contract->url }}" target="_blank">{{ $contract->contract_id }}</a></td>
                                    <td style="
                                    @if(($market->tweets_current - $market->tweets_start) >= $contract->MinTweets && ($market->tweets_current - $market->tweets_start) <= $contract->MaxTweets)
                                        background-color: gold;
                                    @endif
                                    ">{{ $contract->short_name }}</td>


                                    @foreach($columns as $column)
                                        @if($market->status && $market->active)
                                        <td style="{{ isset($market->maxes[$column][$contract->contract_id]) ? 'background-color: rgba(255, 0, 0, 0.5);' : '' }} {{ isset($market->mins[$column][$contract->contract_id]) ? 'background-color: rgba(0, 255, 0, 0.5);' : '' }}" >
                                            @if($contract->history)
                                                {{ $contract->history->{$column} == 0 || $contract->history->{$column} == 1.00 ? 'None' : '$' . $contract->history->{$column} }}
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                        @else 
                                            <td>N/A</td>
                                        @endif
                                    @endforeach

                                    <td>
                                        @if($contract->history)
                                            {{ \Carbon\Carbon::parse($contract->history->created_at)->diffForHumans() }}
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer text-muted text-center">
                {{ $market->ticker_symbol }}
            </div>
        </div>
    </div>
</div>
