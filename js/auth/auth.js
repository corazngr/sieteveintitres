document.addEventListener('DOMContentLoaded', () => {
    const profileDropdown = document.getElementById('profile-dropdown');

    if (profileDropdown) {
        fetch('/sieteveintitres/php/auth/verificar_sesion.php')
            .then(response => response.json())
            .then(session => {
                if (session.loggedIn) {
                    // Usuario ha iniciado sesión
                    profileDropdown.innerHTML = `
                        <a href="/sieteveintitres/html/publico/miusuario.html">Mi Perfil</a>
                        <a href="/sieteveintitres/php/logout.php">Cerrar Sesión</a>
                    `;
                } else {
                    // Usuario no ha iniciado sesión
                    profileDropdown.innerHTML = `
                        <a href="/sieteveintitres/html/publico/iniciosesion.html">Iniciar Sesión</a>
                        <a href="/sieteveintitres/html/publico/registro.html">Regístrate</a>
                    `;
                }
            })
            .catch(error => {
                console.error('Error al verificar la sesión:', error);
                // Menú por defecto en caso de error
                profileDropdown.innerHTML = '<a href="/sieteveintitres/html/publico/iniciosesion.html">Iniciar Sesión</a>';
            });
    }
});