<head>
  <!-- Plotly.js -->
  <script src="https://cdn.plot.ly/plotly-latest.min.js"></script>
</head>

<body>
  
  <div id="chart" style="width: 100%; height: 100%;"></div>
  <script>
    var data = [
        @foreach($columns as $column)
        {
            x: [@foreach($history as $point) '{{ $point->created_at }}', @endforeach],
            y: [@foreach($history as $point) {{ $point->{$column} }}, @endforeach],
            type: 'scatter',
            name: '{{ studly_case($column) }}',
        },
        @endforeach
    ];

    var layout = {
        showlegend: true,
        title: '{{ $contract->long_name }}',
        legend: '{{ $sum }}',
        xaxis: {
            title: 'Time',
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
        annotations: [{
          showarrow: false,
          text: 'Total: {{ $sum }}',
          x: 1, y: 1, xref: 'paper', yref: 'paper'
        }]
    };

    layout.shapes = [

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
                yref: 'paper'  ,
                line: {
                    color: 'rgb(255,30,30)',
                    width: 1
                }
            },
            @endforeach            
        ];

    Plotly.newPlot('chart', data, layout);
  </script>
</body>
