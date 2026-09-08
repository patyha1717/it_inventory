<?php
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/helpers/auth.php';

auth();

if (!in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    echo "Access Denied";
    exit;
}

$pdo = db();

/* FETCH USERS */
$users = $pdo->query("
    SELECT id, name, email 
    FROM users 
    WHERE status = 1 
    ORDER BY name ASC
")->fetchAll(PDO::FETCH_ASSOC);

/* FETCH ALL CATEGORIES */
$categories = $pdo->query("
    SELECT id, name 
    FROM inv_categories 
    ORDER BY name ASC
")->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/header.php';
?>

<!-- <div class="container mt-4"> -->
    <div class="main-content-inner px-4 py-3">

    <h3 class="fw-bold">Bulk Assign Inventory</h3>

    <!-- SUCCESS MESSAGE -->
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success mt-3">
            Items assigned successfully!
        </div>
    <?php endif; ?>

    <!-- ERROR MESSAGE -->
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger mt-3">
            Something went wrong.
        </div>
    <?php endif; ?>

    <div class="card mt-3">
        <div class="card-body">

            <form action="/itms.arukustech.com/public/api/assign_bulk.php" method="POST">

                <!-- USER SECTION -->
                <div class="mb-3">
                    <label class="fw-bold">Select User *</label>
                    <select class="form-control" name="user_id" required>
                        <option value="">-- Select User --</option>
                        <?php foreach ($users as $u): ?>
                            <option value="<?= $u['id'] ?>">
                                <?= $u['name'] ?> (<?= $u['email'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

               
		
                 <!-- LOCATION SECTION -->
                <div class="row mt-3">

                    <!-- STATE -->
                    <div class="col-md-6 mb-3">
                        <label class="fw-bold">State *</label>
                        <select name="state" id="stateSelect" class="form-control" required>
                            <option value="">-- Select State --</option>
                        </select>
                    </div>

                    <!-- CITY -->
                    <div class="col-md-6 mb-3">
                        <label class="fw-bold">City *</label>
                        <select name="city" id="citySelect" class="form-control" required>
                            <option value="">-- Select City --</option>
                        </select>
                    </div>

                </div>

                <!-- CATEGORY DROPDOWN -->
                <label class="fw-bold">Category *</label>
                <select class="form-control mb-3" id="category">
                    <option value="">-- Select Category --</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= $c['name'] ?></option>
                    <?php endforeach; ?>
                </select>

                <!-- BRAND DROPDOWN -->
                <label class="fw-bold">Brand</label>
                <select class="form-control mb-3" id="brand">
                    <option value="">-- Select Brand --</option>
                </select>

                <!-- MODEL DROPDOWN -->
                <label class="fw-bold">Model</label>
                <select class="form-control mb-3" id="model">
                    <option value="">-- Select Model --</option>
                </select>

                
                <!-- SEARCH BOX -->
                <input type="text" id="searchBox" class="form-control mb-3" placeholder="Search items...">

                <!-- SELECT ALL BUTTONS -->
                <div class="mb-2">
                    <button type="button" class="btn btn-sm btn-success" id="selectAll">Select All</button>
                    <button type="button" class="btn btn-sm btn-warning" id="unselectAll">Unselect All</button>
                </div>

                <!-- ITEMS LIST -->
                <label class="fw-bold">Select Items *</label>
                <div id="itemsList" class="border p-3 rounded" style="max-height:350px; overflow-y:auto;">
                    <div class="text-info">Select category to load items...</div>
                </div>

                <!-- REMARKS -->
                <div class="mt-3">
                    <label class="fw-bold">Remarks</label>
                    <textarea name="remarks" class="form-control" rows="3"></textarea>
                </div>

                <button class="btn btn-primary mt-3">Assign Selected Items</button>
                <a href="assigned_list.php" class="btn btn-secondary mt-3">View Assigned List</a>

            </form>

        </div>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>

<!-- ========================= AJAX SCRIPT ====================== -->

<script>
// Load Brands based on Category
document.getElementById("category").addEventListener("change", function () {
    let catId = this.value;

    document.getElementById("brand").innerHTML = '<option value="">Loading...</option>';
    document.getElementById("model").innerHTML = '<option value="">-- Select Model --</option>';
    document.getElementById("itemsList").innerHTML = '<div class="text-info">Loading items...</div>';

    fetch("/itms.arukustech.com/public/api/fetch_inventory_filters.php?category=" + catId)
        .then(res => res.json())
        .then(data => {
            // Brands
            let brandHTML = '<option value="">-- Select Brand --</option>';
            data.brands.forEach(b => {
                brandHTML += `<option value="${b.brand}">${b.brand}</option>`;
            });
            document.getElementById("brand").innerHTML = brandHTML;

            loadItems(); // Load items too
        });
});

// Load Models based on Brand
document.getElementById("brand").addEventListener("change", function () {
    let catId = document.getElementById("category").value;
    let brand = this.value;

    fetch(`/api/fetch_inventory_filters.php?category=${catId}&brand=${brand}`)
        .then(res => res.json())
        .then(data => {
            let modelHTML = '<option value="">-- Select Model --</option>';
            data.models.forEach(m => {
                modelHTML += `<option value="${m.model}">${m.model}</option>`;
            });
            document.getElementById("model").innerHTML = modelHTML;

            loadItems();
        });
});

// Load Items based on Category/Brand/Model
document.getElementById("model").addEventListener("change", loadItems);

// FUNCTION: Load Items List
function loadItems() {
    let cat = document.getElementById("category").value;
    let brand = document.getElementById("brand").value;
    let model = document.getElementById("model").value;

    fetch(`/api/fetch_inventory_items.php?category=${cat}&brand=${brand}&model=${model}`)
        .then(res => res.json())
        .then(data => {
            let html = "";

            if (data.items.length === 0) {
                html = '<div class="text-danger">No matching items found.</div>';
            } else {
                data.items.forEach(i => {
                    html += `
                        <div class="form-check mb-1">
                            <input type="checkbox" class="form-check-input item-check" name="item_ids[]" value="${i.id}">
                            <label class="form-check-label">${i.item_details} (${i.model} - ${i.serial_no})</label>
                        </div>`;
                });
            }

            document.getElementById("itemsList").innerHTML = html;
        });
}

// SEARCH FILTER
document.getElementById("searchBox").addEventListener("keyup", function () {
    let text = this.value.toLowerCase();
    document.querySelectorAll("#itemsList label").forEach(label => {
        if (label.innerText.toLowerCase().includes(text)) {
            label.parentElement.style.display = "";
        } else {
            label.parentElement.style.display = "none";
        }
    });
});

// SELECT ALL
document.getElementById("selectAll").onclick = function () {
    document.querySelectorAll(".item-check").forEach(cb => cb.checked = true);
};

// UNSELECT ALL
document.getElementById("unselectAll").onclick = function () {
    document.querySelectorAll(".item-check").forEach(cb => cb.checked = false);
};
</script>
<script>
// INDIA STATES & CITIES (can be expanded later)
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

const stateSelect = document.getElementById("stateSelect");
const citySelect  = document.getElementById("citySelect");

// Populate states
for (let state in INDIA_LOCATIONS) {
    stateSelect.innerHTML += `<option value="${state}">${state}</option>`;
}

// Load cities when state changes
stateSelect.addEventListener("change", function () {
    citySelect.innerHTML = '<option value="">-- Select City --</option>';
    if (!this.value) return;

    INDIA_LOCATIONS[this.value].forEach(city => {
        citySelect.innerHTML += `<option value="${city}">${city}</option>`;
    });
});
</script>
