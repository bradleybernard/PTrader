<head>
  <script src="https://cdn.plot.ly/plotly-latest.min.js"></script>
</head>

<body>
  
  <div id="chart" style="width: 100%; height: 100%;"></div>
  <script>

    var data_x = [];
    var data_y = [];

    @foreach($sum as $point)
        data_x.push('{{ $point['date'] }}');
        data_y.push({{ $point['sum'] }});
    @endforeach

    var data = [
        {
            x: data_x,
            y: data_y,
            type: 'scatter',
            name: 'Sum of Buy No\'s',
        },
    ];

    var layout = {
        showlegend: true,
        title: '{{ $market->name }} ({{ $market->tweets_current - $market->tweets_start }} tweets)',
        xaxis: {
            title: 'Time ({{ \Carbon\Carbon::now() }})',
            titlefont: {
                family: 'Courier New, monospace',
                size: 18,
                color: '#7f7f7f'
            }
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
            {
                x0: 0,
                x1: 1,
                type: 'line',
                y0: {{ $contracts->count() - 1}},
                y1: {{ $contracts->count() - 1}},
                xref: 'paper',
                yref: 'y',
                line: {
                    color: 'rgb(30,30,30)',
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
            },
        ],
    };

    Plotly.newPlot('chart', data, layout);
  </script>
</body>
