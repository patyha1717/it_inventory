// main.js - global handlers

document.addEventListener("DOMContentLoaded", function () {
    console.log("Main.js ready");

    // Sidebar toggle for mobile (if needed)
    const sidebar = document.querySelector(".sidebar");
    const topbar = document.querySelector(".topbar");

    if (sidebar && topbar) {
        topbar.addEventListener("click", () => {
            if (window.innerWidth < 992) {
                sidebar.classList.toggle("open");
            }
        });
    }
});
