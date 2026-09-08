<div id="iconPickerModal" class="modal fade" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Select an Icon</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">

                <input type="text" id="iconSearch" class="form-control mb-3" placeholder="Search icons...">

                <div id="iconGrid" 
                     style="display:grid; grid-template-columns:repeat(6,1fr); gap:15px; max-height:450px; overflow-y:auto;">
                </div>

            </div>

        </div>
    </div>
</div>

<style>
#iconGrid i {
    font-size: 32px;
    cursor: pointer;
    padding: 10px;
    border-radius: 8px;
    text-align:center;
}
#iconGrid i:hover {
    background: #f3f4f6;
}
</style>

<script>
// LOAD ICON LIST
let icons = [];

// Bootstrap Icons
for (let i = 0; i < 600; i++) {
    icons.push("bi-" + i);
}

// Remix Icons
const remixList = [
    "ri-dashboard-line","ri-bar-chart-line","ri-add-line","ri-edit-line","ri-delete-bin-line",
    "ri-computer-line","ri-user-3-line","ri-settings-3-line","ri-folder-line","ri-checkbox-multiple-line",
    "ri-tools-line","ri-box-3-line","ri-file-list-3-line","ri-shield-check-line"
];
icons = icons.concat(remixList);

// RENDER ICON GRID
function renderIcons(filter = "") {
    const grid = document.getElementById("iconGrid");
    grid.innerHTML = "";

    let filtered = icons.filter(ic => ic.toLowerCase().includes(filter.toLowerCase()));

    filtered.forEach(ic => {
        let el = document.createElement("i");
        el.className = ic;
        el.setAttribute("data-icon", ic);

        el.onclick = function() {
            document.querySelector("[name=icon]").value = ic;
            updatePreview();
            bootstrap.Modal.getInstance(document.getElementById("iconPickerModal")).hide();
        };

        grid.appendChild(el);
    });
}

document.getElementById("iconSearch").addEventListener("input", function() {
    renderIcons(this.value);
});

// initialize
document.addEventListener("DOMContentLoaded", () => {
    renderIcons();
});
</script>
