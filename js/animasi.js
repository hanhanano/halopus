document.addEventListener('DOMContentLoaded', function () {
    // Ambil elemen menu sidebar dan item-item di dalamnya
    const menuItems = document.querySelectorAll('.sidebar-menu li a');
    const sidebarMenu = document.getElementById('sidebarMenu');

    // Efek hover dock pada menu sidebar
    sidebarMenu.addEventListener('mouseover', function (e) {
        const targetItem = e.target.closest('li');
        if (!targetItem) return;

        const allItems = Array.from(sidebarMenu.querySelectorAll('li'));
        const targetIndex = allItems.indexOf(targetItem);

        allItems.forEach((item, index) => {
            const distance = Math.abs(index - targetIndex);
            const link = item.querySelector('a');

            if (distance === 0) {
                link.style.transform = 'translateX(15px) scale(1.08)';
                link.style.zIndex = '10';
            } else if (distance === 1) {
                link.style.transform = 'translateX(8px) scale(1.04)';
                link.style.zIndex = '5';
            } else if (distance === 2) {
                link.style.transform = 'translateX(4px) scale(1.02)';
                link.style.zIndex = '2';
            } else {
                link.style.transform = 'translateX(0) scale(1)';
                link.style.zIndex = '1';
            }
        });
    });

    // Reset efek dock saat kursor keluar dari sidebar
    sidebarMenu.addEventListener('mouseleave', function () {
        const allLinks = sidebarMenu.querySelectorAll('li a');
        allLinks.forEach(link => {
            link.style.transform = 'translateX(0) scale(1)';
            link.style.zIndex = '1';
        });
    });

    // Efek klik menu (tambah class active dan animasi klik)
    menuItems.forEach(item => {
        item.addEventListener('click', function (e) {
            menuItems.forEach(i => i.classList.remove('active'));
            this.classList.add('active');
            this.style.transform = 'scale(0.95)';
            setTimeout(() => {
                this.style.transform = '';
            }, 150);
        });
    });

    // Simulasi pengecekan role user
    const hasRole = (role) => {
        return role === 'super_admin';
    };

    // Menyembunyikan menu admin jika bukan super_admin
    const adminMenu = document.querySelector('.admin-only');
    if (!hasRole('super_admin')) {
        adminMenu.style.display = 'none';
    }
});
