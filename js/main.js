// Fungsionalitas sidebar
document.addEventListener("DOMContentLoaded", () => {
    // Ambil elemen-elemen terkait sidebar
    const hamburgerMenu = document.getElementById("hamburgerMenu");
    const sidebar = document.getElementById("sidebar");
    const sidebarOverlay = document.getElementById("sidebarOverlay");
    const sidebarClose = document.getElementById("sidebarClose");

    // Fungsi untuk membuka dan menutup sidebar
    function toggleSidebar() {
        sidebar.classList.toggle("active");
        sidebarOverlay.classList.toggle("active");
        hamburgerMenu.classList.toggle("active");
        document.body.style.overflow = sidebar.classList.contains("active") ? "hidden" : "";
    }

    // Fungsi untuk menutup sidebar
    function closeSidebar() {
        sidebar.classList.remove("active");
        sidebarOverlay.classList.remove("active");
        hamburgerMenu.classList.remove("active");
        document.body.style.overflow = "";
    }

    // Event klik pada tombol hamburger untuk toggle sidebar
    if (hamburgerMenu) {
        hamburgerMenu.addEventListener("click", toggleSidebar);
    }

    // Event klik pada tombol close sidebar
    if (sidebarClose) {
        sidebarClose.addEventListener("click", closeSidebar);
    }

    // Event klik di area overlay untuk menutup sidebar
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener("click", closeSidebar);
    }

    // Event tekan tombol Escape untuk menutup sidebar
    document.addEventListener("keydown", (e) => {
        if (e.key === "Escape" && sidebar.classList.contains("active")) {
            closeSidebar();
        }
    });

    // Auto-hide alert setelah 5 detik
    setTimeout(() => {
        const alerts = document.querySelectorAll(".alert");
        alerts.forEach((alert) => {
            alert.style.opacity = "0";
            alert.style.transform = "translateY(-20px)";
            setTimeout(() => alert.remove(), 300);
        });
    }, 5000);
});