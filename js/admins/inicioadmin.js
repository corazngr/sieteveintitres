document.addEventListener('DOMContentLoaded', function () {
    const swalDark = {
        background: '#1a1a1a',
        color: '#fff'
    };

    // Lógica Menú Móvil
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.createElement('div');
    overlay.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:1000;display:none;';
    document.body.appendChild(overlay);

    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', () => {
            sidebar.classList.toggle('active');
            overlay.style.display = sidebar.classList.contains('active') ? 'block' : 'none';
        });
    }

    overlay.addEventListener('click', () => {
        sidebar.classList.remove('active');
        overlay.style.display = 'none';
    });

    // Función para cargar los datos del dashboard
    async function cargarDatosDashboard() {
        try {
            const response = await fetch('/sieteveintitres/php/admins/obtener_estatus.php');
            const result = await response.json();

            if (result.success) {
                const data = result.data;
                // Rellenamos el HTML con los datos recibidos
                document.getElementById('adminName').textContent = data.nombre_admin;
                document.getElementById('reservationsCount').textContent = data.reservaciones_hoy;
                document.getElementById('totalCapacity').textContent = data.cupo_total_hoy;
                document.getElementById('dailyIncome').textContent = data.ingresos_dia;
                document.getElementById('newRidersCount').textContent = data.nuevos_riders_mes;
                document.getElementById('activeMembershipsCount').textContent = data.membresias_activas;
                document.getElementById('maintenanceBikesCount').textContent = data.bicis_mantenimiento;
            } else {
                // --- ¡CAMBIO 1: Notificación de error (ej. sesión expirada)! ---
                Swal.fire({
                    title: 'Error de Sesión',
                    text: result.message,
                    icon: 'warning',
                    confirmButtonText: 'Iniciar Sesión',
                    ...swalDark
                }).then(() => {
                    // Redirigimos SÓLO DESPUÉS de que el admin cierre el modal
                    window.location.href = result.redirectUrl || '/sieteveintitres/html/publico/iniciosesion.html';
                });
            }
        } catch (error) {
            console.error('Error al cargar los datos del dashboard:', error);
            document.querySelector('.main-header h1').textContent = 'Error al cargar los datos';
            // --- ¡CAMBIO 2: Notificación de error de conexión! ---
            Swal.fire({
                title: 'Error de Conexión',
                text: 'No se pudieron cargar los datos del dashboard. Revisa tu conexión.',
                icon: 'error',
                confirmButtonText: 'Aceptar',
                ...swalDark
            });
        }
    }

    // Llama a la función para cargar los datos en cuanto la página esté lista
    cargarDatosDashboard();

    // Lógica para el botón de cerrar sesión
    const logoutButton = document.querySelector('.logout-btn');
    if (logoutButton) {
        // --- ¡CAMBIO 3: Añadimos 'async' y reemplazamos confirm()! ---
        logoutButton.addEventListener('click', async function () {
            const confirmation = await Swal.fire({
                title: '¿Cerrar Sesión?',
                text: '¿Estás seguro de que quieres cerrar la sesión?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, cerrar sesión',
                cancelButtonText: 'Cancelar',
                ...swalDark
            });

            if (confirmation.isConfirmed) {
                window.location.href = '/sieteveintitres/php/logout.php';
            }
        });
    }
});