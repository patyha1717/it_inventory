
// app.js - base UI helpers

document.addEventListener("DOMContentLoaded", function () {
    console.log("IT Asset Manager Loaded");

    // Auto-hide alerts
    document.querySelectorAll('.auto-close-alert').forEach(alert => {
        setTimeout(() => alert.style.display = 'none', 3000);
    });
});

// Helper: AJAX loader toggle
function showLoader() {
    const loader = document.createElement("div");
    loader.id = "ajaxLoader";
    loader.style.position = "fixed";
    loader.style.top = "0";
    loader.style.left = "0";
    loader.style.width = "100%";
    loader.style.height = "100%";
    loader.style.background = "rgba(255,255,255,0.6)";
    loader.style.zIndex = "9999";
    loader.innerHTML = '<div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%)">Loading...</div>';
    document.body.appendChild(loader);
}

function hideLoader() {
    const el = document.getElementById("ajaxLoader");
    if (el) el.remove();
}
