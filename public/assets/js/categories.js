/* =================================================================================
   FIXED: Correct API path
================================================================================= */
const API = "/api/";

/* INITIAL LOAD */
$(document).ready(function () {
    loadCategories();
});

/* ==============================
   LOAD CATEGORY LIST
============================== */
function loadCategories() {
    $.get(API + "categories_list.php", function (data) {

        let html = "";

        data.forEach(row => {
            html += `
                <tr>
                    <td>${row.id}</td>
                    <td>${row.name}</td>
                    <td>${row.description ?? ""}</td>
                    <td>
                        <button onclick="deleteCat(${row.id})" class="btn btn-danger btn-sm">
                            Delete
                        </button>
                    </td>
                </tr>
            `;
        });

        $("#tableBody").html(html);

    }, "json").fail(function () {
        alert("API request failed");
    });
}

/* ==============================
   ADD CATEGORY
============================== */
$("#addBtn").click(function () {
    let name = $("#catName").val().trim();
    let desc = $("#catDesc").val().trim();

    if (name === "") {
        alert("Category name required");
        return;
    }

    $.post(API + "categories_add.php",
        { name: name, description: desc },
        function (res) {
            if (res.status === "success") {
                $("#catName").val("");
                $("#catDesc").val("");
                loadCategories();
            } else {
                alert(res.message);
            }
        },
        "json"
    ).fail(function () {
        alert("API request failed");
    });
});

/* ==============================
   DELETE CATEGORY
============================== */
function deleteCat(id) {
    if (!confirm("Delete this category?")) return;

    $.post(API + "categories_delete.php",
        { id: id },
        function (res) {
            if (res.status === "success") {
                loadCategories();
            } else {
                alert(res.message);
            }
        },
        "json"
    ).fail(function () {
        alert("API request failed");
    });
}
