(function () {
    "use strict";

    const sidebarToggle = document.querySelector("[data-admin-sidebar]");
    const sidebar = document.querySelector(".admin-sidebar");

    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener("click", function () {
            sidebar.classList.toggle("show");
        });
    }
})();
