<head>
  <script src="https://cdn.plot.ly/plotly-latest.min.js"></script>
</head>

<body>
  
  <div id="activity" style="width: 100%; height: 100%;"></div>
  <div id="prices" style="width: 100%; height: 100%;"></div>
  <script>
    var activity = [
        @if($tweets->count() != 0)
            {
                x: [@foreach($tweets as $tweet) '{{ $tweet->api_created_at }}', @endforeach],
                y: [@foreach($tweets as $tweet) {{ $tweet->value}}, @endforeach],
                type: 'scatter',
                name: 'Tweets',
            },
        @endif
    ];

    var prices = [
        @foreach($columns as $column)
            @foreach($contracts as $contract)
            {
                x: [@foreach($history[$contract->contract_id] as $point) '{{ $point->created_at }}', @endforeach],
                y: [@foreach($history[$contract->contract_id] as $point) {{ $point->{$column} }}, @endforeach],
                type: 'scatter',
                name: '{{ $contract->short_name }} {{ studly_case($column) }}',
            },
            @endforeach
        @endforeach
    ];

    var pricesLayout = {
        showlegend: true,
        title: '{{ $market->name }}',
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
        }
    };

    var activityLayout = {
        showlegend: true,
        title: '{{ $market->name }}',
        xaxis: {
            title: 'Time',
            titlefont: {
                family: 'Courier New, monospace',
                size: 18,
                color: '#7f7f7f'
            }
        },
        yaxis: {
            title: 'Tweet Count',
            titlefont: {
                family: 'Courier New, monospace',
                size: 18,
                color: '#7f7f7f'
            }
        }
    };

    Plotly.newPlot('prices', prices, pricesLayout);
    Plotly.newPlot('activity', activity, activityLayout);
  </script>
</body>
