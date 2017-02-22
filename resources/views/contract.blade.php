<html>
    <head>
      <script src="https://cdn.plot.ly/plotly-latest.min.js"></script>
      <title>{{ $contract->short_name }} in {{ $contract->market->ticker_symbol }}</title>
    </head>

    <body>
        <div id="chart" style="width: 100%; height: 100%;"></div>
        <script>
            @foreach($columns as $column)
            var x_{{ $column }} = [];
            var y_{{ $column }} = [];
            @endforeach

            @foreach($history as $point) 
                @foreach($point as $key => $val)
                    @if($key == 'created_at')
                        x_{{ $key }}.push('{{ $val }}');
                    @else 
                        y_{{ $key }}.push({{ $val }});
                    @endif
                @endforeach
            @endforeach

            var data = [
                @foreach($columns as $column)
                {
                    x: x_{{ $column }},
                    y: y_{{ $column }},
                    type: 'scatter',
                    name: '({{ $contract->short_name }}) {{ studly_case($column) }}',
                },
                @endforeach
            ];

            var layout = {
                showlegend: true,
                title: '{{ $contract->long_name }} ({{ $market->tweets_current - $market->tweets_start }} tweets)',
                xaxis: {
                    title: 'Time ({{ \Carbon\Carbon::now() }})',
                    titlefont: {
                        family: 'Courier New, monospace',
                        size: 18,
                        color: '#7f7f7f'
                    },
                    range: ['{{ $market->date_start }}', '{{ $contract->market->date_end }}'],
                },
                yaxis: {
                    title: 'Price',
                    titlefont: {
                        family: 'Courier New, monospace',
                        size: 18,
                        color: '#7f7f7f'
                    }
                },
                shapes: [

                    @foreach($tweets as $tweet)
                    {
                        x0: '{{ $tweet->api_created_at }}',
                        x1: '{{ $tweet->api_created_at }}',
                        type: 'line',
                        y0: 0,
                        y1: 1,
                        xref: 'x',
                        yref: 'paper'  ,
                        line: {
                            color: 'rgb(30,255,30)',
                            width: 1
                        }
                    },
                    @endforeach   

                    @foreach($deleted as $tweet)
                    {
                        x0: '{{ $tweet->api_created_at }}',
                        x1: '{{ $tweet->api_created_at }}',
                        type: 'line',
                        y0: 0,
                        y1: 1,
                        xref: 'x',
                        yref: 'paper',
                        line: {
                            color: 'rgb(255,30,30)',
                            width: 1
                        }
                    },
                    @endforeach  
                    {
                        x0: '{{ $market->date_start }}',
                        x1: '{{ $market->date_start }}',
                        type: 'line',
                        y0: 0,
                        y1: 1,
                        xref: 'x',
                        yref: 'paper',
                        line: {
                            color: 'rgb(30,255,30)',
                            width: 3
                        }
                    },
                    {
                        x0: '{{ $market->date_end }}',
                        x1: '{{ $market->date_end }}',
                        type: 'line',
                        y0: 0,
                        y1: 1,
                        xref: 'x',
                        yref: 'paper',
                        line: {
                            color: 'rgb(255,30,30)',
                            width: 3
                        }
                    },
                ],
                annotations: [
                    @foreach($all as $tweet) 
                    {
                        text: '{{ $tweet->value }}',
                        x: '{{ $tweet->api_created_at }}',
                        y: 1.03,
                        xref: 'x',
                        yref: 'paper',
                        showarrow: false,
                        xanchor: 'center',
                        yanchor: 'center',
                    },
                    @endforeach
                    {
                        text: 'START',
                        x: '{{ $market->date_start }}',
                        y: 1.03,
                        xref: 'x',
                        yref: 'paper',
                        showarrow: false,
                        xanchor: 'center',
                        yanchor: 'center',
                    },
                    {
                        text: 'END',
                        x: '{{ $market->date_end }}',
                        y: 1.03,
                        xref: 'x',
                        yref: 'paper',
                        showarrow: false,
                        xanchor: 'center',
                        yanchor: 'center',
                    }
                ],
            };

            Plotly.newPlot('chart', data, layout);
      </script>
    </body>
</html>
