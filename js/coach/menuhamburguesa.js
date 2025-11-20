document.addEventListener('DOMContentLoaded', () => {
    // ===============================================
    // === 1. LÓGICA DEL MENÚ HAMBURGUESA
    // ===============================================
    const menuToggle = document.getElementById('menu-toggle');
    const sidebarNav = document.getElementById('sidebar-nav');

    if (menuToggle && sidebarNav) {
        // Mostrar/Ocultar menú al hacer clic en la hamburguesa
        menuToggle.addEventListener('click', (e) => {
            e.stopPropagation(); // Evita que otros clics se disparen
            sidebarNav.classList.toggle('is-active');

            // Cambia el ícono de hamburguesa a una "X" y viceversa
            if (sidebarNav.classList.contains('is-active')) {
                menuToggle.innerHTML = '&times;'; // Símbolo 'X'
            } else {
                menuToggle.innerHTML = '☰'; // Símbolo hamburguesa
            }
        });

        // Opcional: Cerrar el menú si se hace clic en un enlace
        sidebarNav.querySelectorAll('.nav-item').forEach(item => {
            item.addEventListener('click', () => {
                sidebarNav.classList.remove('is-active');
                menuToggle.innerHTML = '☰';
            });
        });

        // Opcional: Cerrar el menú si se hace clic FUERA de él
        document.addEventListener('click', (e) => {
            if (sidebarNav.classList.contains('is-active') && !sidebarNav.contains(e.target) && e.target !== menuToggle) {
                sidebarNav.classList.remove('is-active');
                menuToggle.innerHTML = '☰';
            }
        });
    }
});