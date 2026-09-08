<?php
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/helpers/auth.php';
auth();

$pdo = db();

/* RENTED CATEGORIES */
$categories = $pdo->query("
    SELECT id, name 
    FROM rented_categories 
    WHERE status = 1
    ORDER BY name ASC
")->fetchAll(PDO::FETCH_ASSOC);

/* USERS (COMMON) */
$users = $pdo->query("
    SELECT id, name, email 
    FROM users 
    WHERE status = 1
    ORDER BY name ASC
")->fetchAll(PDO::FETCH_ASSOC);

/* DYNAMIC PROJECTS */
$projects = $pdo->query("
    SELECT id, project_name 
    FROM rented_projects 
    ORDER BY project_name ASC
")->fetchAll(PDO::FETCH_ASSOC);

/* FETCH AVAILABLE RENTED INVENTORY */
$items = $pdo->query("
    SELECT id, category_id, product_name, serial_no
    FROM rented_inventory
    WHERE status = 'available'
    ORDER BY product_name ASC
")->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/header.php';
?>

<div class="main-content-inner px-4 py-3">
    <div class="container-fluid mt-3 px-4">

        <h3 class="fw-bold mb-3"><i class="fa fa-share-square text-primary me-2"></i>Assign Rented Inventory</h3>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success border-0 shadow-sm"><i class="fa fa-check-circle me-2"></i>Assignment completed successfully!</div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger border-0 shadow-sm"><i class="fa fa-exclamation-triangle me-2"></i>Something went wrong.</div>
        <?php endif; ?>

        <ul class="nav nav-tabs mb-3 border-0">
            <li class="nav-item">
                <a class="nav-link active fw-bold border-0" data-bs-toggle="tab" href="#singleAssign">
                    <i class="fa fa-user me-1"></i> Single Assign
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link fw-bold border-0" data-bs-toggle="tab" href="#bulkAssign">
                    <i class="fa fa-users me-1"></i> Bulk Assign
                </a>
            </li>
        </ul>

        <div class="tab-content">

            <div class="tab-pane fade show active" id="singleAssign">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <form method="POST" action="/itms.arukustech.com/public/api/rented_assign.php">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="fw-bold small">Select Category *</label>
                                    <select id="category-select" class="form-control form-select" required>
                                        <option value="">-- Select Category --</option>
                                        <?php foreach ($categories as $c): ?>
                                            <option value="<?= $c['id'] ?>"><?= $c['name'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="fw-bold small">Select Item *</label>
                                    <select name="rented_inventory_id" id="item-select" class="form-control form-select" required>
                                        <option value="">-- Select Item --</option>
                                    </select>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="fw-bold small">Select User *</label>
                                    <select name="user_id" class="form-control form-select" required>
                                        <option value="">-- Select User --</option>
                                        <?php foreach ($users as $u): ?>
                                            <option value="<?= $u['id'] ?>">
                                                <?= htmlspecialchars($u['name']) ?> (<?= htmlspecialchars($u['email']) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="fw-bold small">State *</label>
                                    <select name="state" id="state-select" class="form-control form-select" required>
                                        <option value="">-- Select State --</option>
                                    </select>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="fw-bold small">City *</label>
                                    <select name="city" id="city-select" class="form-control form-select" required>
                                        <option value="">-- Select City --</option>
                                    </select>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="fw-bold small">Project Name</label>
                                    <select name="project_name" class="form-control form-select">
                                        <option value="">-- Select Project --</option>
                                        <?php foreach ($projects as $pj): ?>
                                            <option value="<?= htmlspecialchars($pj['project_name']) ?>"><?= htmlspecialchars($pj['project_name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="fw-bold small">Remarks</label>
                                <textarea name="remarks" class="form-control" rows="3" placeholder="Enter any specific notes..."></textarea>
                            </div>

                            <button class="btn btn-primary px-4"><i class="fa fa-check me-1"></i> Assign Item</button>
                            <a href="rented_assign_list.php" class="btn btn-secondary ms-2"><i class="fa fa-list me-1"></i> View Assigned List</a>
                        </form>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="bulkAssign">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <form method="POST" action="/itms.arukustech.com/public/api/rented_assign_bulk.php">
                            <div class="mb-3">
                                <label class="fw-bold small">Select User *</label>
                                <select class="form-control form-select" name="user_id" required>
                                    <option value="">-- Select User --</option>
                                    <?php foreach ($users as $u): ?>
                                        <option value="<?= $u['id'] ?>">
                                            <?= htmlspecialchars($u['name']) ?> (<?= htmlspecialchars($u['email']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="fw-bold small">State *</label>
                                    <select name="state" id="stateSelect" class="form-control form-select" required>
                                        <option value="">-- Select State --</option>
                                    </select>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="fw-bold small">City *</label>
                                    <select name="city" id="citySelect" class="form-control form-select" required>
                                        <option value="">-- Select City --</option>
                                    </select>
                                </div>
                            </div>

                            <label class="fw-bold small">Category *</label>
                            <select class="form-control form-select mb-3" id="category">
                                <option value="">-- Select Category --</option>
                                <?php foreach ($categories as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= $c['name'] ?></option>
                                <?php endforeach; ?>
                            </select>

                            <label class="fw-bold small">Search Items</label>
                            <div class="input-group mb-2">
                                <span class="input-group-text bg-white"><i class="fa fa-search text-muted"></i></span>
                                <input type="text" id="searchBox" class="form-control" placeholder="Type serial or product name...">
                            </div>

                            <div class="mb-2">
                                <button type="button" class="btn btn-sm btn-outline-success" id="selectAll"><i class="fa fa-check-double"></i> Select All</button>
                                <button type="button" class="btn btn-sm btn-outline-warning ms-1" id="unselectAll"><i class="fa fa-times"></i> Unselect All</button>
                            </div>

                            <label class="fw-bold small">Select Items *</label>
                            <div id="itemsList" class="border p-3 rounded bg-light" style="max-height:350px; overflow-y:auto;">
                                <div class="text-info small"><i class="fa fa-info-circle me-1"></i>Select category to load items...</div>
                            </div>

                            <div class="mt-3">
                                <label class="fw-bold small">Project Name</label>
                                <select name="project_name" class="form-control form-select">
                                    <option value="">-- Select Project --</option>
                                    <?php foreach ($projects as $pj): ?>
                                        <option value="<?= htmlspecialchars($pj['project_name']) ?>"><?= htmlspecialchars($pj['project_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mt-3">
                                <label class="fw-bold small">Remarks</label>
                                <textarea name="remarks" class="form-control" rows="3" placeholder="Enter bulk remarks..."></textarea>
                            </div>

                            <button class="btn btn-primary mt-3 px-4"><i class="fa fa-layer-group me-1"></i> Assign Selected Items</button>
                            <a href="rented_assign_list.php" class="btn btn-secondary mt-3 ms-2"><i class="fa fa-list me-1"></i> View Assigned List</a>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
/* ================= RENTED INVENTORY DATA ================= */
const RENTED_ITEMS = <?= json_encode($items); ?>;

/* ================= SINGLE ASSIGN ================= */
document.getElementById("category-select").addEventListener("change", function () {
    const catID = this.value;
    const itemDropdown = document.getElementById("item-select");

    itemDropdown.innerHTML = '<option value="">-- Select Item --</option>';
    if (!catID) return;

    RENTED_ITEMS
        .filter(i => i.category_id == catID)
        .forEach(i => {
            itemDropdown.innerHTML += `<option value="${i.id}">${i.product_name} (${i.serial_no})</option>`;
        });
});

/* ================= BULK ASSIGN ================= */
document.getElementById("category").addEventListener("change", function () {
    const catID = this.value;
    let html = "";

    RENTED_ITEMS
        .filter(i => i.category_id == catID)
        .forEach(i => {
            html += `
                <div class="form-check mb-1">
                    <input type="checkbox" 
                        class="form-check-input item-check" 
                        name="rented_inventory_ids[]" 
                        value="${i.id}" id="item_${i.id}">
                    <label class="form-check-label small" for="item_${i.id}">
                        ${i.product_name} <span class="text-muted">(${i.serial_no})</span>
                    </label>
                </div>`;
        });

    document.getElementById("itemsList").innerHTML = 
        html || '<div class="text-danger small"><i class="fa fa-times-circle me-1"></i>No available items in this category.</div>';
});

/* SEARCH */
document.getElementById("searchBox").addEventListener("keyup", function () {
    let t = this.value.toLowerCase();
    document.querySelectorAll("#itemsList .form-check").forEach(div => {
        div.style.display = div.innerText.toLowerCase().includes(t) ? "" : "none";
    });
});

document.getElementById("selectAll").onclick = () => 
    document.querySelectorAll(".item-check").forEach(c => { if(c.parentElement.style.display !== "none") c.checked = true; });

document.getElementById("unselectAll").onclick = () => 
    document.querySelectorAll(".item-check").forEach(c => c.checked = false);

/* STATE & CITY LOGIC */
const INDIA_LOCATIONS = {
   "Andhra Pradesh": ["Visakhapatnam", "Vijayawada", "Guntur", "Tirupati", "Nellore", "Kurnool", "Rajahmundry", "Eluru", "Kadapa"],
  "Arunachal Pradesh": ["Itanagar", "Naharlagun", "Pasighat", "Tawang"],
  "Assam": ["Guwahati", "Dibrugarh", "Silchar", "Jorhat", "Tezpur", "Nagaon", "Tinsukia"],
  "Bihar": ["Patna", "Gaya", "Bhagalpur", "Muzaffarpur", "Darbhanga", "Purnia", "Ara", "Begusarai"],
  "Chhattisgarh": ["Raipur", "Bilaspur", "Durg", "Bhilai", "Korba", "Raigarh", "Jagdalpur"],
  "Goa": ["Panaji", "Margao", "Vasco da Gama", "Mapusa", "Ponda"],
  "Gujarat": ["Ahmedabad", "Surat", "Vadodara", "Rajkot", "Bhavnagar", "Jamnagar", "Junagadh", "Anand", "Gandhinagar"],
  "Haryana": ["Gurgaon", "Faridabad", "Panipat", "Ambala", "Yamunanagar", "Rohtak", "Hisar", "Karnal"],
  "Himachal Pradesh": ["Shimla", "Solan", "Dharamshala", "Mandi", "Una", "Hamirpur", "Bilaspur"],
  "Jharkhand": ["Ranchi", "Jamshedpur", "Dhanbad", "Bokaro", "Deoghar", "Hazaribagh", "Giridih"],
  "Karnataka": ["Bengaluru", "Mysuru", "Mangaluru", "Hubli", "Dharwad", "Belagavi", "Ballari", "Shivamogga", "Tumakuru", "Udupi"],
  "Kerala": ["Kochi", "Thiruvananthapuram", "Kozhikode", "Thrissur", "Palakkad", "Alappuzha", "Kollam", "Kannur", "Kottayam"],
  "Madhya Pradesh": ["Bhopal", "Indore", "Jabalpur", "Gwalior", "Ujjain", "Sagar", "Satna", "Rewa", "Chhindwara", "Dewas"],
  "Maharashtra": ["Mumbai", "Pune", "Nagpur", "Nashik", "Thane", "Navi Mumbai", "Aurangabad", "Solapur", "Kolhapur", "Amravati", "Jalgaon"],
  "Manipur": ["Imphal", "Thoubal", "Churachandpur"],
  "Meghalaya": ["Shillong", "Tura", "Jowai", "Nongstoin"],
  "Mizoram": ["Aizawl", "Lunglei", "Champhai"],
  "Nagaland": ["Kohima", "Dimapur", "Mokokchung", "Tuensang"],
  "Odisha": ["Bhubaneswar", "Cuttack", "Rourkela", "Sambalpur", "Berhampur", "Balasore", "Baripada", "Jharsuguda"],
  "Punjab": ["Chandigarh", "Ludhiana", "Amritsar", "Jalandhar", "Patiala", "Bathinda", "Hoshiarpur", "Moga"],
  "Rajasthan": ["Jaipur", "Jodhpur", "Udaipur", "Kota", "Ajmer", "Bikaner", "Alwar", "Bhilwara", "Sikar"],
  "Sikkim": ["Gangtok", "Namchi", "Gyalshing", "Mangan"],
  "Tamil Nadu": ["Chennai", "Coimbatore", "Madurai", "Salem", "Tiruchirappalli", "Tirunelveli", "Erode", "Vellore", "Thoothukudi", "Kanchipuram"],
  "Telangana": ["Hyderabad", "Warangal", "Nizamabad", "Karimnagar", "Khammam", "Ramagundam", "Mahbubnagar"],
  "Tripura": ["Agartala", "Udaipur", "Dharmanagar"],
  "Uttar Pradesh": ["Lucknow", "Noida", "Ghaziabad", "Kanpur", "Varanasi", "Agra", "Meerut", "Prayagraj", "Bareilly", "Aligarh", "Moradabad"],
  "Uttarakhand": ["Dehradun", "Haridwar", "Roorkee", "Haldwani", "Rudrapur", "Nainital"],
  "West Bengal": ["Kolkata", "Howrah", "Durgapur", "Asansol", "Siliguri", "Bardhaman", "Malda", "Kharagpur"],

  "Andaman and Nicobar Islands": ["Port Blair"],
  "Chandigarh": ["Chandigarh"],
  "Dadra and Nagar Haveli and Daman and Diu": ["Daman", "Silvassa", "Diu"],
  "Delhi": ["New Delhi", "Dwarka", "Rohini", "Saket", "Karol Bagh"],
  "Jammu and Kashmir": ["Srinagar", "Jammu", "Anantnag", "Baramulla"],
  "Ladakh": ["Leh", "Kargil"],
  "Lakshadweep": ["Kavaratti"],
  "Puducherry": ["Puducherry", "Karaikal", "Mahe", "Yanam"]
};

function bindStateCity(stateId, cityId) {
    const s = document.getElementById(stateId);
    const c = document.getElementById(cityId);
    
    // Populate States
    for (let st in INDIA_LOCATIONS) {
        s.innerHTML += `<option value="${st}">${st}</option>`;
    }
    
    s.addEventListener("change", () => {
        c.innerHTML = '<option value="">-- Select City --</option>';
        if(INDIA_LOCATIONS[s.value]){
            INDIA_LOCATIONS[s.value].forEach(ci => c.innerHTML += `<option value="${ci}">${ci}</option>`);
        }
    });
}

bindStateCity("state-select", "city-select");
bindStateCity("stateSelect", "citySelect");
</script>

<style>
    .nav-tabs .nav-link { border-radius: 0; color: #666; }
    .nav-tabs .nav-link.active { border-bottom: 3px solid #007bff; color: #007bff; background: none; }
    .form-check-input:checked { background-color: #28a745; border-color: #28a745; }
</style>

<?php include __DIR__ . '/footer.php'; ?>