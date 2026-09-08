<?php
require_once __DIR__ . '/../app/db.php';
include 'header.php';

$database = db();
$today = date('Y-m-d');

// Fetch TODAY'S stats specifically
$stmt = $database->prepare("SELECT * FROM app_analytics WHERE slug = 'total_url_hits' AND analytics_date = ?");
$stmt->execute([$today]);
$statsToday = $stmt->fetch();

// Fetch ALL-TIME stats for the "All-time" labels
$totalStmt = $database->query("SELECT SUM(hits) as total_hits, SUM(successful_logins) as total_logins FROM app_analytics WHERE slug = 'total_url_hits'");
$allTime = $totalStmt->fetch();

// History for the table
$historyStmt = $database->query("SELECT * FROM app_analytics WHERE slug = 'total_url_hits' ORDER BY analytics_date DESC LIMIT 7");
$history = $historyStmt->fetchAll();

$hits = $statsToday['hits'] ?? 0;
$logins = $statsToday['successful_logins'] ?? 0;
$conversion = ($hits > 0) ? ($logins / $hits) * 100 : 0;
?>
<div class="container-fluid p-4">
    <div class="row mb-4 align-items-center">
        <div class="col">
            <h3 class="fw-bold mb-0"><i class="ri-calendar-check-line text-primary me-2"></i>Today's Traffic Analytics</h3>
            <p class="text-muted small mb-0">Date: <strong><?= date('d M, Y') ?></strong></p>
        </div>
        <div class="col-auto">
            <button onclick="location.reload()" class="btn btn-white shadow-sm border-0 fw-bold px-3" style="border-radius: 10px;">
                <i class="ri-refresh-line me-1"></i> Sync Data
            </button>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4" style="border-radius: 15px;">
                <h6 class="text-uppercase fw-bold text-secondary small">Today's URL Hits</h6>
                <h2 class="fw-bold text-primary mb-1"><?= number_format($hits) ?></h2>
                <span class="text-muted extra-small">All-time: <?= number_format($allTime['total_hits'] ?? 0) ?></span>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4" style="border-radius: 15px;">
                <h6 class="text-uppercase fw-bold text-secondary small">Today's Logins</h6>
                <h2 class="fw-bold text-success mb-1"><?= number_format($logins) ?></h2>
                <span class="text-muted extra-small">All-time: <?= number_format($allTime['total_logins'] ?? 0) ?></span>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4" style="border-radius: 15px;">
                <h6 class="text-uppercase fw-bold text-secondary small">Login Rate</h6>
                <h2 class="fw-bold text-dark mb-1"><?= round($conversion) ?>%</h2>
                <div class="progress" style="height: 6px;">
                    <div class="progress-bar bg-primary" style="width: <?= $conversion ?>%"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm" style="border-radius: 15px;">
        <div class="card-header bg-white border-0 py-3"><h5 class="fw-bold mb-0">Last 7 Days Activity</h5></div>
        <div class="table-responsive p-3">
            <table class="table align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>URL Hits</th>
                        <th>Successful Logins</th>
                        <th>Success Rate</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($history as $row): ?>
                    <tr>
                        <td class="fw-bold"><?= date('d M, Y', strtotime($row['analytics_date'])) ?></td>
                        <td><?= number_format($row['hits']) ?></td>
                        <td><?= number_format($row['successful_logins']) ?></td>
                        <td>
                            <?php $rate = ($row['hits'] > 0) ? ($row['successful_logins'] / $row['hits']) * 100 : 0; ?>
                            <span class="text-primary small fw-bold"><?= round($rate, 1) ?>%</span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>.extra-small { font-size: 0.75rem; }</style>
<?php include 'footer.php'; ?>