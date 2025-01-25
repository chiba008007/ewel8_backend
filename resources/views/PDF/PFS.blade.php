<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />

        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    </head>
    <body >
        <div style="width: 500px; height: 500px;">
            <canvas id="radarChart"></canvas>
            <button id=btn>aaa</button>
        </div>
        <script>
        var ctx = document.getElementById('radarChart').getContext('2d');
        var radarChart = new Chart(ctx, {
            type: 'radar',
            data: {
                labels: ['A', 'B', 'C', 'D', 'E'],
                datasets: [{
                    label: 'Dataset 1',
                    data: [5, 10, 15, 20, 25],
                    borderColor: 'rgba(255, 99, 132, 1)',
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    fill: true
                }]
            },
            options: {
                scales: {
                    r: {
                        beginAtZero: true
                    }
                }
            }
        });

        document.getElementById("btn").addEventListener("click",function(){
            var canvas = document.getElementById('radarChart');
            var imgData = canvas.toDataURL('image/png');
            console.log(imgData);

            axios.post('/save-radar-image', {
                image: imgData
            }).then(response => {
                console.log('Image saved successfully');
            }).catch(error => {
                console.log('Error saving image:', error);
            });
        });
    </script>
    </body>
</html>
