<?php
include __DIR__ . "/header.php";
require_once __DIR__ . '/../app/db.php';
$pdo = db();

/* ------------------------------------------------
    DATA FETCHING
------------------------------------------------- */
$sql = "SELECT c.id, c.name AS category,
        SUM(CASE WHEN i.status = 'available' THEN 1 ELSE 0 END) AS available_count,
        SUM(CASE WHEN i.status = 'assigned' THEN 1 ELSE 0 END) AS assigned_count
        FROM inv_categories c
        LEFT JOIN inventory_items i ON c.id = i.category_id
        GROUP BY c.id, c.name ORDER BY c.name ASC";

$stmt = $pdo->query($sql);
$categoryData = $stmt->fetchAll(PDO::FETCH_ASSOC);

$status = $pdo->query("SELECT
    SUM(CASE WHEN status='available' THEN 1 ELSE 0 END) AS available,
    SUM(CASE WHEN status='assigned' THEN 1 ELSE 0 END) AS assigned,
    SUM(CASE WHEN status='maintenance' THEN 1 ELSE 0 END) AS maintenance,
    SUM(CASE WHEN status='faulty' THEN 1 ELSE 0 END) AS faulty,   -- Added
    SUM(CASE WHEN status='missing' THEN 1 ELSE 0 END) AS missing,  -- Added
    SUM(CASE WHEN status='retired' THEN 1 ELSE 0 END) AS retired
    FROM inventory_items")->fetch(PDO::FETCH_ASSOC);

$categoryLabels = [];
$availableArr   = [];
$assignedArr    = [];

foreach ($categoryData as $cat) {
    $categoryLabels[] = $cat['category'];
    $availableArr[]   = (int)$cat['available_count'];
    $assignedArr[]    = (int)$cat['assigned_count'];
}
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.3.0/fonts/remixicon.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>

    <style>
        :root {
            --bg: #f8fafc;
            --text: #0f172a;
            --muted: #64748b;
            --border: #e2e8f0;
        }

        body, html {
            height: 100vh;
            margin: 0;
            padding: 0;
            font-family: 'Inter', system-ui, sans-serif;
            background: 
                radial-gradient(circle at 8% 15%, rgba(99,102,241,0.07) 0%, transparent 18%),
                radial-gradient(circle at 92% 82%, rgba(16,185,129,0.06) 0%, transparent 22%),
                var(--bg);
            color: var(--text);
            /* CRITICAL FIX: Lock the page height and hide page-level scrollbars */
            overflow: hidden !important; 
        }

        .dashboard-wrapper {
            /* ADJUSTMENT: Subtracted 150px to make room for header and footer */
            height: calc(100vh - 150px); 
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 15px;
            box-sizing: border-box;
        }

        /* Stat Cards */
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            flex-shrink: 0;
        }

        .stat-card {
            border-radius: 16px;
            padding: 24px 20px;
            color: white;
            height: 100px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow: hidden;
            transition: all 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
            box-shadow: 0 6px 12px -4px rgba(0,0,0,0.12);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 20px -10px rgba(0,0,0,0.18);
        }

        .stat-card h3 {
            font-size: 2.8rem;
            font-weight: 800;
            margin: 0;
            line-height: 1;
            letter-spacing: -1.5px;
            z-index: 2;
        }

        .stat-card p {
            font-size: 0.8rem;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 0.8px;
            margin-bottom: 4px;
            opacity: 0.95;
            z-index: 2;
        }

        .stat-card i {
            position: absolute;
            right: 20px;
            bottom: 10px;
            font-size: 60px;
            opacity: 0.15;
            z-index: 1;
            transform: rotate(-12deg);
        }

        .bg-avail  { background: linear-gradient(135deg, #a5b4fc 0%, #6366f1 100%); }
        .bg-assign { background: linear-gradient(135deg, #6ee7b7 0%, #10b981 100%); }
        .bg-maint  { background: linear-gradient(135deg, #fcd34d 0%, #f59e0b 100%); }
        .bg-retire { background: linear-gradient(135deg, #fca5a5 0%, #ef4444 100%); }
        .bg-faulty { background: linear-gradient(135deg, #f87171 0%, #dc2626 100%); } 
        .bg-missing { background: linear-gradient(135deg, #38bdf8 0%, #0284c7 100%); }


        /* Main Layout */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 420px;
            gap: 20px;
            flex: 1;
            min-height: 0; /* Critical for inner scrolling */
        }

        .glass-panel {
            background: rgba(255,255,255,0.92);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 1px solid rgba(226,232,240,0.8);
            padding: 24px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
            display: flex;
            flex-direction: column;
            min-height: 0;
        }

        .glass-panel h6 {
            font-size: 0.82rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.4px;
            color: var(--muted);
            margin: 0 0 16px 0;
            flex-shrink: 0;
        }

        /* Sidebar Categories */
        .sidebar-scroll {
            flex: 1;
            overflow-y: auto;
            padding-right: 8px;
            scrollbar-width: none; /* Firefox */
        }

        .sidebar-scroll::-webkit-scrollbar {
            display: none; /* Hide scrollbar for cleaner auto-scroll look */
        }

        .cat-item {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 14px 12px;
            border-radius: 12px;
            transition: all 0.2s ease;
            border-bottom: 1px solid #f1f5f9;
        }

        .cat-item:hover {
            background: rgba(248,250,252,0.8);
        }

        .cat-img-wrapper {
            width: 60px;
            height: 60px;
            flex-shrink: 0;
            background: white;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .cat-img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .cat-info { flex: 1; }

        .cat-name {
            font-size: 1rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .cat-meta {
            font-size: 0.75rem;
            color: var(--muted);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
        }

        .status-high   { background: #ef4444; }
        .status-medium { background: #f59e0b; }
        .status-low    { background: #10b981; }

        .mini-bar {
            height: 8px;
            border-radius: 4px;
            background: #e2e8f0;
            overflow: hidden;
            margin-top: 8px;
            display: flex;
        }

        /* Add this to your existing <style> section */
          .stat-grid a {
          text-decoration: none !important;
          display: block;
          color: inherit;
          } 

        .bar-avail  { background: #6366f1; height: 100%; }
        .bar-assign { background: #10b981; height: 100%; }

        @media (max-width: 1100px) {
            .dashboard-grid { grid-template-columns: 1fr 320px; }
        }
    </style>
</head>
<body>

<div class="dashboard-wrapper">

    <div class="stat-grid">
    <a href="master_reports.php?status=available">
        <div class="stat-card bg-avail">
            <p>Available</p>
            <h3 class="count-up" data-target="<?= number_format($status['available']) ?>">0</h3>
            <i class="ri-archive-line"></i>
        </div>
    </a>

    <a href="master_reports.php?status=assigned">
        <div class="stat-card bg-assign">
            <p>Assigned</p>
            <h3 class="count-up" data-target="<?= number_format($status['assigned']) ?>">0</h3>
            <i class="ri-user-shared-line"></i>
        </div>
    </a>

    <a href="master_reports.php?status=maintenance">
        <div class="stat-card bg-maint">
            <p>Maintenance</p>
            <h3 class="count-up" data-target="<?= number_format($status['maintenance']) ?>">0</h3>
            <i class="ri-tools-line"></i>
        </div>
    </a>

    <a href="master_reports.php?status=faulty">
        <div class="stat-card bg-faulty">
            <p>Faulty</p>
            <h3 class="count-up" data-target="<?= number_format($status['faulty']) ?>">0</h3>
            <i class="ri-error-warning-line"></i>
        </div>
    </a>

    <a href="master_reports.php?status=missing">
        <div class="stat-card bg-missing">
            <p>Missing</p>
            <h3 class="count-up" data-target="<?= number_format($status['missing']) ?>">0</h3>
            <i class="ri-question-line"></i>
        </div>
    </a>

    <a href="master_reports.php?status=retired">
        <div class="stat-card bg-retire">
            <p>Retired</p>
            <h3 class="count-up" data-target="<?= number_format($status['retired']) ?>">0</h3>
            <i class="ri-delete-bin-line"></i>
        </div>
    </a>
</div>

    <div class="dashboard-grid">

        <div class="glass-panel">
            <h6>Stock Comparison by Category</h6>
            <div style="flex: 1; min-height: 0; position: relative;">
                <canvas id="mainChart"></canvas>
            </div>
        </div>

        <div class="glass-panel">
            <h6>Categories Summary</h6>
            <div class="sidebar-scroll" id="autoScrollSide">
    <?php foreach ($categoryData as $cat):
        $total = $cat['available_count'] + $cat['assigned_count'];
        $ratio = $total > 0 ? ($cat['assigned_count'] / $total) * 100 : 0;
        $statusClass = $ratio > 70 ? 'high' : ($ratio > 30 ? 'medium' : 'low');

        $imageName = strtolower($cat['category']) . ".png";
        $imagePath = "assets/images/categories/" . $imageName;
        $displayImg = file_exists(__DIR__ . "/../public/" . $imagePath) 
            ? $imagePath 
            : "assets/images/categories/default.png";
    ?>
    <a href="master_reports.php?category_id=<?= $cat['id'] ?>" style="text-decoration: none; color: inherit; display: block;">
        <div class="cat-item">
            <div class="cat-img-wrapper">
                <img src="<?= $displayImg ?>" class="cat-img" alt="<?= htmlspecialchars($cat['category']) ?>" 
                     onerror="this.src='assets/images/categories/default.png'">
            </div>
            <div class="cat-info">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="cat-name"><?= htmlspecialchars($cat['category']) ?></div>
                        <div class="cat-meta">
                            <span>Avail: <strong class="text-primary"><?= $cat['available_count'] ?></strong></span>
                            <span>Assigned: <strong class="text-success"><?= $cat['assigned_count'] ?></strong></span>
                            <span class="status-dot status-<?= $statusClass ?>"></span>
                        </div>
                    </div>
                    <span class="badge bg-white text-dark fw-bold border border-secondary-subtle px-2 py-1" 
                          style="font-size:0.75rem;"><?= $total ?></span>
                </div>

                <div class="mini-bar">
                    <div class="bar-avail" style="width: <?= $total ? ($cat['available_count']/$total*100) : 0 ?>%"></div>
                    <div class="bar-assign" style="width: <?= $total ? ($cat['assigned_count']/$total*100) : 0 ?>%"></div>
                </div>
            </div>
        </div>
    </a>
    <?php endforeach; ?>
</div>
        </div>

    </div>
</div>

<?php include __DIR__ . "/footer.php"; ?>

<script>
// 1. Count-up animation
document.querySelectorAll('.count-up').forEach(el => {
    const targetValue = el.dataset.target.replace(/,/g, '');
    const target = parseInt(targetValue) || 0;
    let count = 0;
    const duration = 1400;
    const step = target / (duration / 16);

    const timer = setInterval(() => {
        count += step;
        if (count >= target) {
            el.textContent = target.toLocaleString();
            clearInterval(timer);
        } else {
            el.textContent = Math.floor(count).toLocaleString();
        }
    }, 16);
});

// 2. Sidebar Auto-Scroll logic
const scrollContainer = document.getElementById('autoScrollSide');
let scrollInterval;
let scrollSpeed = 0.5; // Pixels per step

function startAutoScroll() {
    if (scrollContainer.scrollHeight <= scrollContainer.clientHeight) return;
    
    scrollInterval = setInterval(() => {
        scrollContainer.scrollTop += scrollSpeed;
        
        if (scrollContainer.scrollTop + scrollContainer.clientHeight >= scrollContainer.scrollHeight - 1) {
            clearInterval(scrollInterval);
            setTimeout(() => {
                scrollContainer.scrollTo({ top: 0, behavior: 'smooth' });
                setTimeout(startAutoScroll, 2000); 
            }, 2000);
        }
    }, 30);
}

scrollContainer.addEventListener('mouseenter', () => clearInterval(scrollInterval));
scrollContainer.addEventListener('mouseleave', startAutoScroll);
startAutoScroll();

// 3. Chart
new Chart(document.getElementById('mainChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($categoryLabels) ?>,
        datasets: [
            {
                label: 'Available',
                data: <?= json_encode($availableArr) ?>,
                backgroundColor: '#6366f1',
                borderRadius: 8,
                barThickness: 28
            },
            {
                label: 'Assigned',
                data: <?= json_encode($assignedArr) ?>,
                backgroundColor: '#10b981',
                borderRadius: 8,
                barThickness: 28
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    boxWidth: 12,
                    padding: 20,
                    font: { size: 13, family: 'Inter', weight: 600 }
                }
            }
        },
        scales: {
            y: {
                grid: { color: '#e2e8f0' },
                border: { display: false },
                ticks: { font: { size: 11 } }
            },
            x: {
                grid: { display: false },
                ticks: { font: { size: 12, weight: '600' } }
            }
        }
    }
});
</script>
</body>
</html>