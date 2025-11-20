document.addEventListener('DOMContentLoaded', function () {

    // Selecciona el botón de cerrar sesión
    const logoutButton = document.querySelector('.logout-btn');

    // Si el botón existe, le añade un evento de clic
    if (logoutButton) {

        // --- ¡CAMBIO 1: Hacemos la función 'async' para poder usar 'await'! ---
        logoutButton.addEventListener('click', async function () {

            // --- ¡CAMBIO 2: Reemplazamos confirm() por Swal.fire()! ---
            const confirmation = await Swal.fire({
                title: '¿Cerrar Sesión?',
                text: '¿Estás seguro de que quieres cerrar la sesión?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, cerrar sesión',
                cancelButtonText: 'Cancelar',
                background: '#1a1a1a', // Tema oscuro
                color: '#fff'         // Texto blanco
            });

            // Si el usuario confirma, lo redirige al script de logout
            if (confirmation.isConfirmed) {
                window.location.href = '/sieteveintitres/php/logout.php';
            }
        });
    }
});