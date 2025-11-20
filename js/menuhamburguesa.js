document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.getElementById('menu-toggle');
    const nav = document.getElementById('nav');

    // Verificar que existen
    if (!toggle || !nav) return;

    const icon = toggle.querySelector("i");

    toggle.addEventListener('click', () => {
        nav.classList.toggle('show');

        // Cambiar ícono entre hamburguesa y "X"
        if (nav.classList.contains('show')) {
            icon.classList.remove("fa-bars");
            icon.classList.add("fa-times");
        } else {
            icon.classList.remove("fa-times");
            icon.classList.add("fa-bars");
        }
    });
});
