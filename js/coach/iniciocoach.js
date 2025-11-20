document.addEventListener('DOMContentLoaded', () => {
    const welcomeHeader = document.getElementById('welcome-header');
    const classListContainer = document.getElementById('class-card-list');

    // ✅ ESTE ES EL ÚNICO "OÍDO" QUE NECESITAS. MANEJA TODO.
    classListContainer.addEventListener('click', async (event) => {
        const button = event.target.closest('.attendance-btn');
        if (!button) return;

        const asistenciaId = button.dataset.asistenciaId;
        const status = button.dataset.status;
        const optionsContainer = button.parentElement;

        optionsContainer.querySelectorAll('.attendance-btn').forEach(btn => btn.disabled = true);

        try {
            const response = await fetch('/sieteveintitres/php/coaches/actualizar_asistencia.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id_asistencia: asistenciaId, estatus: status })
            });
            const result = await response.json();

            if (result.success) {
                optionsContainer.querySelectorAll('.attendance-btn').forEach(btn => btn.classList.remove('selected'));
                button.classList.add('selected');
            } else {
                // --- ¡CAMBIO 1: Notificación de error! ---
                Swal.fire({
                    title: 'Error al Actualizar',
                    text: result.message,
                    icon: 'error',
                    confirmButtonText: 'Entendido',
                    background: '#1a1a1a',
                    color: '#fff'
                });
            }
        } catch (error) {
            console.error('Error al actualizar asistencia:', error);
            // --- ¡CAMBIO 2: Notificación de error de conexión! ---
            Swal.fire({
                title: 'Error de Conexión',
                text: 'No se pudo actualizar la asistencia. Revisa tu conexión.',
                icon: 'error',
                confirmButtonText: 'Aceptar',
                background: '#1a1a1a',
                color: '#fff'
            });
        } finally {
            optionsContainer.querySelectorAll('.attendance-btn').forEach(btn => btn.disabled = false);
        }
    });

    // --- EL RESTO DE TU CÓDIGO (NO CAMBIA) ---

    async function cargarDatosCoach() {
        try {
            const response = await fetch('/sieteveintitres/php/coaches/datos_coach.php');
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const result = await response.json();

            if (result.success) {
                const data = result.data;
                welcomeHeader.innerHTML = `¡Bienvenido, Coach ${data.nombre_coach}! 👋`;
                classListContainer.innerHTML = '';

                if (data.clases.length === 0) {
                    classListContainer.innerHTML = '<p>No tienes clases programadas próximamente. ¡A descansar!</p>';
                    return;
                }

                data.clases.forEach(clase => {
                    const classCardHTML = crearTarjetaClase(clase);
                    classListContainer.insertAdjacentHTML('beforeend', classCardHTML);
                });

            } else {
                welcomeHeader.textContent = 'Error';
                classListContainer.innerHTML = `<p>Error al cargar los datos: ${result.message}</p>`;
                // --- ¡CAMBIO 3: Notificación de error al cargar datos! ---
                Swal.fire({
                    title: 'Error al Cargar Datos',
                    text: result.message,
                    icon: 'error',
                    confirmButtonText: 'Aceptar',
                    background: '#1a1a1a',
                    color: '#fff'
                });
            }
        } catch (error) {
            console.error('Error en fetch:', error);
            classListContainer.innerHTML = '<p>No se pudo conectar con el servidor. Intenta más tarde.</p>';
            // --- ¡CAMBIO 4: Notificación de error de conexión! ---
            Swal.fire({
                title: 'Error de Conexión',
                text: 'No se pudo conectar con el servidor. Intenta más tarde.',
                icon: 'error',
                confirmButtonText: 'Aceptar',
                background: '#1a1a1a',
                color: '#fff'
            });
        }
    }

    function crearTarjetaClase(clase) {
        const fecha = new Date(`${clase.fecha}T${clase.hora_inicio}`);
        const diaSemana = fecha.toLocaleDateString('es-ES', { weekday: 'long' });
        const hora = fecha.toLocaleTimeString('es-ES', { hour: 'numeric', minute: '2-digit', hour12: true });

        return `
        <div class="class-info-card">
            <div class="class-details">
                <div class="class-time"><i class="fa-solid fa-clock"></i> ${diaSemana}, ${hora}</div>
                <div class="class-participants">
                    <i class="fa-solid fa-users"></i> ${clase.inscritos}/${clase.cupo_maximo} Riders inscritos
                </div>
            </div>

            </div>
    `;
    }

    cargarDatosCoach();
});