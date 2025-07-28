<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
</head>
<body>
    <h1>Hello</h1>
    <canvas id="revenueChart" height="100"></canvas>



    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('livewire:load', () => {
            console.log('📊 analytics-dashboard script running');

            const ctx = document.getElementById('revenueChart');
            if (!ctx) {
                console.error('❌ Canvas #revenueChart not found');
                return;
            }

            // If a previous instance exists, destroy it.
            if (ctx._chart) ctx._chart.destroy();

            // Dummy values to verify rendering
            const labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May'];
            const values = [100, 250, 175, 300, 225];

            console.log('📈 Drawing dummy chart', { labels, values });

            ctx._chart = new Chart(ctx.getContext('2d'), {
                type: 'line',
                data: {
                    labels,
                    datasets: [{
                        label: 'Dummy Revenue',
                        data: values,
                        fill: false,
                        tension: 0.3,
                        borderColor: '#28a745',
                        pointBackgroundColor: '#28a745'
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { callback: v => '$' + v.toFixed(2) }
                        }
                    },
                    plugins: { legend: { display: true } }
                }
            });
        });
    </script>

</body>
</html>