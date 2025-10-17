// admin/assets/admin_script.js

document.addEventListener('DOMContentLoaded', () => {
    const ctx = document.getElementById('userChart');

    if (ctx) {
        new Chart(ctx, {
            type: 'line', // Grafik tipi: 'line', 'bar'
            data: {
                labels: userChartLabels, // PHP'den gelen etiketler
                datasets: [{
                    label: 'Yeni Kullanıcılar',
                    data: userChartData, // PHP'den gelen veriler
                    fill: true,
                    borderColor: 'rgb(52, 152, 219)',
                    backgroundColor: 'rgba(52, 152, 219, 0.2)',
                    tension: 0.3
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1 // Sadece tam sayıları göster
                        }
                    }
                }
            }
        });
    }
});