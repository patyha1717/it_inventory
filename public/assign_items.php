<?php
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/helpers/auth.php';
auth();

$pdo = db();

/* -----------------------------
   FETCH CATEGORIES
--------------------------------*/
$categories = $pdo->query("
    SELECT id, name 
    FROM inv_categories 
    ORDER BY name ASC
")->fetchAll(PDO::FETCH_ASSOC);

/* -----------------------------
   FETCH ACTIVE USERS
--------------------------------*/
$users = $pdo->query("
    SELECT id, name, email 
    FROM users 
    WHERE status = 1
    ORDER BY name ASC
")->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/header.php'; 
?>

 <div class="main-content-inner px-4 py-3">
    <div class="container-fluid mt-3 px-4">

        <h3 class="fw-bold mb-3">Assign Inventory Item</h3>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">Item assigned successfully!</div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger">
                <?php
                    switch ($_GET['error']) {
                        case "denied": echo "You do not have permission!"; break;
                        case "invalid": echo "Invalid request!"; break;
                        case "missing": echo "Please select both item and user."; break;
                        case "not_available": echo "Item is no longer available."; break;
                        case "failed": echo "Assignment failed. Try again."; break;
                        default: echo "Something went wrong.";
                    }
                ?>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-body">

                <form method="POST" action="/api/assign_item.php">

                    <div class="row">

                        <!-- CATEGORY -->
                        <div class="col-md-4 mb-3">
                            <label class="fw-bold">Select Category *</label>
                            <select id="category-select" class="form-control" required>
                                <option value="">-- Select Category --</option>
                                <?php foreach ($categories as $c): ?>
                                    <option value="<?= $c['id']; ?>"><?= $c['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- ITEM -->
                        <div class="col-md-4 mb-3">
                            <label class="fw-bold">Select Item *</label>
                            <select name="item_id" id="item-select" class="form-control" required>
                                <option value="">-- Select Item --</option>
                            </select>
                        </div>

                        <!-- USER -->
                        <div class="col-md-4 mb-3">
                            <label class="fw-bold">Select User *</label>
                            <select name="user_id" class="form-control" required>
                                <option value="">-- Select User --</option>
                                <?php foreach ($users as $u): ?>
                                    <option value="<?= $u['id']; ?>">
                                        <?= $u['name']; ?> (<?= $u['email']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- STATE -->
                        <div class="col-md-4 mb-3">
                            <label class="fw-bold">State *</label>
                            <select name="state" id="state-select" class="form-control" required>
                                <option value="">-- Select State --</option>
                            </select>
                        </div>

                        <!-- CITY -->
                        <div class="col-md-4 mb-3">
                            <label class="fw-bold">City *</label>
                            <select name="city" id="city-select" class="form-control" required>
                                <option value="">-- Select City --</option>
                            </select>
                        </div>

                    </div>

                    <div class="mb-3">
                        <label class="fw-bold">Remarks</label>
                        <textarea name="remarks" class="form-control" rows="3"></textarea>
                    </div>

                    <div class="mt-3">
                        <button class="btn btn-primary">Assign Item</button>
                        <a href="assigned_list.php" class="btn btn-secondary ms-2">View Assigned List</a>
                    </div>

                </form>

            </div>
        </div>

    </div>
</div>

<!-- EXISTING AJAX (UNCHANGED) -->
<script>
document.getElementById("category-select").addEventListener("change", function () {

    let catID = this.value;
    let itemDropdown = document.getElementById("item-select");

    itemDropdown.innerHTML = '<option value="">Loading...</option>';

    if (!catID) {
        itemDropdown.innerHTML = '<option value="">-- Select Item --</option>';
        return;
    }

    fetch("/itms.arukustech.com/public/api/get_items_by_category.php?cat_id=" + catID)
        .then(res => res.json())
        .then(data => {
            itemDropdown.innerHTML = '<option value="">-- Select Item --</option>';
            data.forEach(item => {
                itemDropdown.innerHTML += `
                    <option value="${item.id}">
                        ${item.item_details} (${item.serial_no})
                    </option>`;
            });
        });
});
</script>

<!-- NEW STATE & CITY SCRIPT -->
<script>
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

const stateSelect = document.getElementById("state-select");
const citySelect  = document.getElementById("city-select");

for (let state in INDIA_LOCATIONS) {
    stateSelect.innerHTML += `<option value="${state}">${state}</option>`;
}

stateSelect.addEventListener("change", function () {
    citySelect.innerHTML = '<option value="">-- Select City --</option>';
    if (!this.value) return;

    INDIA_LOCATIONS[this.value].forEach(city => {
        citySelect.innerHTML += `<option value="${city}">${city}</option>`;
    });
});
</script>

<?php include __DIR__ . '/footer.php'; ?>
