<head>
  <!-- Plotly.js -->
  <script src="https://cdn.plot.ly/plotly-latest.min.js"></script>
</head>

<body>
  
  <div id="chart" style="width: 100%; height: 100%;"></div>
  <script>
    var data = [
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

    var layout = {
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

    Plotly.newPlot('chart', data, layout);
  </script>
</body>
