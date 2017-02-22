<html>
    <head>
      <script src="https://cdn.plot.ly/plotly-latest.min.js"></script>
      <title>{{ $contract->short_name }} in {{ $contract->market->ticker_symbol }}</title>
    </head>

    <body>
        <div id="chart" style="width: 100%; height: 100%;"></div>
        <script>
            @foreach($columns as $column)
            var data_x_{{ $column }} = [];
            var data_y = [];
            @endforeach

            @foreach($history as $point) 
                @foreach($columns as $key)
                        data_x_{{ $key }}.push({{ $point->$key }});
                @endforeach
                data_y.push('{{ $point->created_at }}');
            @endforeach

            var data = [
                @foreach($columns as $column)
                {
                    x: data_x_{{ $column }},
                    y: data_y,
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
