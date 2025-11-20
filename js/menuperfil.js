document.addEventListener('DOMContentLoaded', () => {

    const menuToggle = document.getElementById('profile-menu-toggle');
    const dropdown = document.getElementById('profile-dropdown');

    // 1. Mostrar/ocultar el menú al hacer clic en el ícono
    menuToggle.addEventListener('click', (event) => {
        // Detiene la propagación para que el clic no llegue al 'window' y cierre el menú inmediatamente
        event.stopPropagation(); 
        dropdown.classList.toggle('show');
    });

    // 2. Cerrar el menú si se hace clic en cualquier otro lugar de la página
    window.addEventListener('click', (event) => {
        // Si el menú está abierto y el clic fue FUERA del menú y FUERA del botón
        if (dropdown.classList.contains('show') && !menuToggle.contains(event.target)) {
            dropdown.classList.remove('show');
        }
    });

});