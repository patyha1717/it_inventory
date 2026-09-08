<?php
// DEBUG ENABLED
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/helpers/auth.php';

check_login();
if (!is_superadmin()) {
    die("Access denied");
}

$pdo = db();

$logs = $pdo->query("
    SELECT a.*, u.name 
    FROM user_activity_log a
    LEFT JOIN users u ON a.user_id = u.id
    ORDER BY a.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/header.php';
?>

<style>
    /* MAXIMIZE PAGE USAGE & SHIFT UP */
    .main-content-inner { padding-top: 5px !important; margin-top: -10px; }
    
    /* SCROLLABLE TABLE CONTAINER */
    .activity-log-container {
        height: calc(100vh - 160px); 
        overflow-y: auto;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        background: #fff;
    }

    /* STICKY TABLE HEADER */
    #activityTable thead th {
        position: sticky;
        top: 0;
        z-index: 10;
        background: #1e293b !important;
        color: white !important;
        font-size: 0.75rem;
        text-transform: uppercase;
        border: none;
        padding: 12px 10px !important;
    }

    .sr-no-text { 
        font-size: 1.15rem; 
        font-weight: 700; 
        color: #1e293b; 
    }

    .badge-action {
        font-size: 0.7rem;
        text-transform: uppercase;
        padding: 4px 10px;
        border-radius: 6px;
        font-weight: 600;
    }

    /* DETAILS BOLD & WRAP */
    .log-details {
        font-weight: 700;
        color: #334155;
        line-height: 1.4;
        white-space: normal; /* Ensures text wraps */
        word-break: break-word;
    }

    /* Custom Scrollbar */
    .activity-log-container::-webkit-scrollbar { width: 6px; }
    .activity-log-container::-webkit-scrollbar-track { background: #f1f1f1; }
    .activity-log-container::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
</style>

<div class="main-content-inner px-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold mb-0"><i class="ri-history-line me-2 text-primary"></i>User Activity Log</h4>
            <p class="text-muted small mb-0">System-wide audit trail for Superadmins</p>
        </div>
        <div>
            <input type="text" id="logSearch" class="form-control form-control-sm shadow-sm" style="width: 250px;" placeholder="Search action, user or details...">
        </div>
    </div>

    <div class="activity-log-container">
        <table id="activityTable" class="table table-hover align-middle bg-white mb-0">
            <thead>
                <tr>
                    <th class="text-center" style="width: 80px;">SR#</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Details</th>
                    <th>IP Address</th>
                    <th>Timestamp</th>
                </tr>
            </thead>
            <tbody id="logBody">
                <?php 
                $sr_no = 1; 
                foreach ($logs as $l): 
                    $actionColor = 'bg-light text-dark border';
                    $action = strtolower($l['action']);
                    if(str_contains($action, 'delete')) $actionColor = 'bg-danger text-white';
                    if(str_contains($action, 'update') || str_contains($action, 'edit')) $actionColor = 'bg-info text-white';
                    if(str_contains($action, 'add') || str_contains($action, 'create')) $actionColor = 'bg-success text-white';
                    if(str_contains($action, 'login')) $actionColor = 'bg-primary text-white';
                ?>
                    <tr>
                        <td class="text-center"><span class="sr-no-text"><?= $sr_no++ ?></span></td>
                        <td>
                            <div class="fw-bold text-dark"><?= htmlspecialchars($l['name'] ?: 'System') ?></div>
                        </td>
                        <td>
                            <span class="badge-action <?= $actionColor ?>">
                                <?= htmlspecialchars($l['action']) ?>
                            </span>
                        </td>
                        <td style="max-width: 500px;">
                            <div class="small log-details">
                                <?= nl2br(htmlspecialchars($l['details'])) ?>
                            </div>
                        </td>
                        <td class="font-monospace small text-muted"><?= $l['ip_address'] ?></td>
                        <td>
                            <div class="fw-bold small text-dark"><?= date('d M Y', strtotime($l['created_at'])) ?></div>
                            <div class="text-muted small" style="font-size: 0.7rem;"><?= date('h:i A', strtotime($l['created_at'])) ?></div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.getElementById('logSearch').addEventListener('keyup', function() {
    let value = this.value.toLowerCase();
    let rows = document.querySelectorAll('#logBody tr');
    
    rows.forEach(row => {
        row.style.display = row.innerText.toLowerCase().includes(value) ? '' : 'none';
    });
});
</script>

<?php include __DIR__ . '/footer.php'; ?>