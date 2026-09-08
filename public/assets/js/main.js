// main.js

document.addEventListener("DOMContentLoaded", () => {
    console.log("Main.js loaded");

    const sidebar = document.querySelector(".sidebar");
    const mainContent = document.querySelector(".main-content");
    const topbar = document.querySelector(".topbar");

    // ------------------------------
    // 1) Prevent null errors anywhere
    // ------------------------------
    if (!sidebar) {
        console.warn("Sidebar not found on this page (safe to ignore)");
        return;
    }

    // ------------------------------
    // 2) Auto-expand on hover (desktop)
    // ------------------------------
    sidebar.addEventListener("mouseenter", () => {
        if (window.innerWidth >= 992) {
            sidebar.classList.add("expanded");
            if (mainContent) {
                mainContent.classList.add("sidebar-expanded");
            }
        }
    });

    sidebar.addEventListener("mouseleave", () => {
        if (window.innerWidth >= 992) {
            sidebar.classList.remove("expanded");
            if (mainContent) {
                mainContent.classList.remove("sidebar-expanded");
            }
        }
    });

    // ------------------------------
    // 3) Mobile toggle
    // ------------------------------
    if (topbar) {
        topbar.addEventListener("click", () => {
            if (window.innerWidth < 992) {
                sidebar.classList.toggle("open");
            }
        });
    }
});
