<?php
require '../_base.php';

// Security: Admin Only
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Admin') {
    redirect('/');
}

$_title = 'Business Analytics - N°9 Perfume';
include '../_head.php';
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    $(document).ready(function() { window.scrollTo(0, 0); })
</script>

<div class="dashboard-container">
    <div class="dashboard-header">
        <h2>📊 Sales Dashboard</h2>
        <a href="productList.php" class="btn-cancel">← Back to Products</a>
    </div>

    <div class="report-grid">
        
        <div class="chart-box full-width">
            <div class="chart-header">
                <h3>Weekly Performance (Last 7 Days)</h3>
            </div>
            <canvas id="weeklyChart" height="80"></canvas>
        </div>

        <div class="chart-box">
            <div class="chart-header">
                <h3>Yearly Revenue</h3>
                <div class="chart-controls">
                    <select id="yearSelect" class="chart-select">
                        <?php 
                        $currentYear = date('Y');
                        for($y = $currentYear; $y >= $currentYear - 2; $y--) {
                            echo "<option value='$y'>$y</option>";
                        }
                        ?>
                    </select>
                    <button onclick="loadYearly()" class="btn-chart-filter">Go</button>
                </div>
            </div>
            <canvas id="yearlyChart" height="200"></canvas>
        </div>

        <div class="chart-box">
            <div class="chart-header">
                <h3>Top Selling Products</h3>
                <div class="chart-controls">
                    <select id="prodMonth" class="chart-select">
                        <?php 
                        for($m=1; $m<=12; $m++) {
                            $sel = ($m == date('n')) ? 'selected' : '';
                            $name = date('M', mktime(0,0,0,$m, 1));
                            echo "<option value='$m' $sel>$name</option>";
                        }
                        ?>
                    </select>
                    <select id="prodYear" class="chart-select">
                        <option value="<?= date('Y') ?>"><?= date('Y') ?></option>
                        <option value="<?= date('Y')-1 ?>"><?= date('Y')-1 ?></option>
                    </select>
                    <button onclick="loadProducts()" class="btn-chart-filter">Go</button>
                </div>
            </div>
            <canvas id="productChart" height="200"></canvas>
        </div>

    </div>
</div>

<script>
// Chart Instances
let weeklyChartInstance = null;
let yearlyChartInstance = null;
let productChartInstance = null;

$(document).ready(function() {
    loadWeekly();
    loadYearly();
    loadProducts();
});

// --- 1. LOAD WEEKLY (Last 7 Days) ---
function loadWeekly() {
    $.get('/api/stats_monthly.php', function(res) {
        const ctx = document.getElementById('weeklyChart').getContext('2d');
        if (weeklyChartInstance) weeklyChartInstance.destroy();

        weeklyChartInstance = new Chart(ctx, {
            type: 'line',
            data: {
                labels: res.labels,
                datasets: [{
                    label: 'Daily Sales (RM)',
                    data: res.data,
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.3,
                    pointRadius: 4
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { 
                    y: { beginAtZero: true, ticks: { callback: v => 'RM ' + v } } 
                }
            }
        });
    });
}

// --- 2. LOAD YEARLY SALES ---
function loadYearly() {
    const year = $('#yearSelect').val();
    
    $.get('/api/stats_yearly.php', { year: year }, function(res) {
        const ctx = document.getElementById('yearlyChart').getContext('2d');
        if (yearlyChartInstance) yearlyChartInstance.destroy();

        yearlyChartInstance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: res.labels,
                datasets: [{
                    label: `Revenue ${year} (RM)`,
                    data: res.data,
                    backgroundColor: 'rgba(212, 175, 55, 0.8)', // Gold
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true } }
            }
        });
    });
}

// --- 3. LOAD PRODUCT SALES ---
function loadProducts() {
    const m = $('#prodMonth').val();
    const y = $('#prodYear').val();

    $.get('/api/stats_products.php', { month: m, year: y }, function(res) {
        const ctx = document.getElementById('productChart').getContext('2d');
        if (productChartInstance) productChartInstance.destroy();

        productChartInstance = new Chart(ctx, {
            type: 'doughnut', // Pie/Doughnut is nice for "Top Products" share
            data: {
                labels: res.labels,
                datasets: [{
                    data: res.data,
                    backgroundColor: [
                        '#111', '#333', '#555', '#777', '#999',
                        '#D4AF37', '#E5C158', '#F6D379'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                cutout: '60%', // Makes it a doughnut
                plugins: {
                    legend: { position: 'right', labels: { boxWidth: 10 } }
                }
            }
        });
    });
}
</script>

<?php include '../_foot.php'; ?>