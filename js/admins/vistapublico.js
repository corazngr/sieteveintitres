document.addEventListener('DOMContentLoaded', function () {
    const swalDark = {
        background: '#1a1a1a',
        color: '#fff'
    };

    // Lógica para menú móvil
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

    // --- Sección de Eventos ---
    loadEvents();
    const eventsGallery = document.getElementById('events-gallery');
    if (eventsGallery) {
        // --- ¡CAMBIO 1: Hacemos la función 'async' para usar 'await'! ---
        eventsGallery.addEventListener('click', async function (e) {
            const deleteButton = e.target.closest('.btn-delete');
            if (deleteButton) {
                const eventId = deleteButton.dataset.id;

                // --- ¡CAMBIO 2: Reemplazo de confirm() por Swal! ---
                const confirmation = await Swal.fire({
                    title: '¿Eliminar Evento?',
                    text: `¿Estás seguro de que quieres eliminar este evento? (ID: ${eventId})`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#d33',
                    ...swalDark
                });

                if (confirmation.isConfirmed) {
                    deleteEvent(eventId, deleteButton);
                }
            }
        });
    }

    // --- Sección de Coaches ---
    loadCoachesForSelect(); // Llena el dropdown
    loadCoaches();          // Carga las tarjetas de coaches existentes

    const coachesGallery = document.getElementById('coaches-gallery');
    if (coachesGallery) {
        // --- ¡CAMBIO 3: Hacemos la función 'async' para usar 'await'! ---
        coachesGallery.addEventListener('click', async function (e) {
            const deleteButton = e.target.closest('.btn-delete');
            if (deleteButton) {
                const coachId = deleteButton.dataset.id;

                // --- ¡CAMBIO 4: Reemplazo de confirm() por Swal! ---
                const confirmation = await Swal.fire({
                    title: '¿Eliminar Perfil?',
                    text: `¿Estás seguro de que quieres eliminar este perfil de coach? (ID: ${coachId})`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#d33',
                    ...swalDark
                });

                if (confirmation.isConfirmed) {
                    deleteCoach(coachId, deleteButton);
                }
            }
        });
    }

    // --- Mostrar mensajes de estado (para ?status=success) ---
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('status')) {
        const status = urlParams.get('status');

        // --- ¡CAMBIO 5: Notificación de Éxito! ---
        if (status === 'success') {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: '¡Operación exitosa!',
                showConfirmButton: false,
                timer: 2000,
                ...swalDark
            });
            // --- ¡CAMBIO 6: Notificación de Error! ---
        } else if (status === 'error') {
            Swal.fire({
                title: 'Error en la Operación',
                text: 'Revisa los datos e intenta de nuevo.',
                icon: 'error',
                confirmButtonText: 'Entendido',
                ...swalDark
            });
        }
        window.history.replaceState({}, document.title, window.location.pathname);
    }

});


// ===============================================
// =========== FUNCIONES DE EVENTOS ==============
// ===============================================

// (Esta es tu línea 41 aprox.)
async function loadEvents() {
    const gallery = document.getElementById('events-gallery');
    if (!gallery) return;
    gallery.innerHTML = '<p style="color: #a0a0a0;">Cargando eventos...</p>';

    try {
        const response = await fetch('/sieteveintitres/php/admins/obtener_evento.php');
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

        const eventos = await response.json();

        if (eventos.length === 0) {
            gallery.innerHTML = '<p style="color: #a0a0a0;">No hay eventos para mostrar. ¡Sube el primero!</p>';
            return;
        }

        gallery.innerHTML = '';
        eventos.forEach(evento => {
            const eventCard = document.createElement('div');
            eventCard.className = 'event-card';
            eventCard.innerHTML = `
                <img src="${evento.ruta_imagen}" alt="${evento.titulo}">
                <div class="event-card-content">
                    <h4>${evento.titulo}</h4>
                    <p>${evento.descripcion}</p>
                </div>
                <div class="event-card-actions">
                    <button class="btn-delete" data-id="${evento.id}">
                        <i class="fa-solid fa-trash"></i> Eliminar
                    </button>
                </div>
            `;
            gallery.appendChild(eventCard);
        });

    } catch (error) {
        console.error('Error al cargar eventos:', error);
        gallery.innerHTML = '<p style="color: #e74c3c;">Error al cargar los eventos.</p>';
        // --- ¡CAMBIO 7: Notificación de error al cargar! ---
        Swal.fire({
            title: 'Error al Cargar Eventos',
            text: 'No se pudieron cargar los eventos. Revisa tu conexión.',
            icon: 'error',
            confirmButtonText: 'Aceptar',
            ...swalDark // Reutilizamos el tema oscuro
        });
    }
}

function deleteEvent(id, button) {
    const eventCard = button.closest('.event-card');
    const data = { id: id };

    fetch('/sieteveintitres/php/admins/eliminar_evento.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                eventCard.remove();
                // --- ¡CAMBIO 8: Notificación de Éxito! ---
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'Evento eliminado con éxito',
                    showConfirmButton: false,
                    timer: 2000,
                    ...swalDark
                });
            } else {
                // --- ¡CAMBIO 9: Notificación de Error! ---
                Swal.fire({
                    title: 'Error al Eliminar',
                    text: result.message,
                    icon: 'error',
                    confirmButtonText: 'Entendido',
                    ...swalDark
                });
            }
        })
        .catch(error => {
            console.error('Error en la solicitud:', error);
            // --- ¡CAMBIO 10: Notificación de Error de Conexión! ---
            Swal.fire({
                title: 'Error de Conexión',
                text: 'No se pudo eliminar el evento.',
                icon: 'error',
                confirmButtonText: 'Aceptar',
                ...swalDark
            });
        });
}


// ===============================================
// ============ FUNCIONES DE COACHES =============
// ===============================================

/**
 * Llena el <select> con coaches sin perfil
 */
async function loadCoachesForSelect() {
    const select = document.getElementById('coach-id');
    if (!select) return;

    try {
        const response = await fetch('/sieteveintitres/php/admins/lista_coaches.php');
        if (!response.ok) throw new Error('Error al cargar lista de coaches');

        const coaches = await response.json();

        if (coaches.length > 0) {
            select.innerHTML = '<option value="">-- Selecciona un coach --</option>'; // Opción default
            coaches.forEach(coach => {
                const option = document.createElement('option');
                option.value = coach.id_coach; // El ID de la tabla 'coaches'
                option.textContent = coach.nombre_coach; // El Nombre de la tabla 'coaches'
                select.appendChild(option);
            });
            select.disabled = false; // Habilita el select
        } else {
            select.innerHTML = '<option value="">-- No hay coaches sin perfil --</option>';
            select.disabled = true; // Deshabilita el select si no hay opciones
        }
    } catch (error) {
        console.error('Error:', error);
        select.innerHTML = '<option value="">-- Error al cargar coaches --</option>';
        select.disabled = true;
        Swal.fire({
            title: 'Error al Cargar Coaches',
            text: 'No se pudo cargar la lista de coaches para el formulario.',
            icon: 'error',
            confirmButtonText: 'Aceptar',
            ...swalDark
        });
    }
}


async function loadCoaches() {
    const gallery = document.getElementById('coaches-gallery');
    if (!gallery) return;
    gallery.innerHTML = '<p style="color: #a0a0a0;">Cargando perfiles de coaches...</p>';

    try {
        const response = await fetch('/sieteveintitres/php/admins/obtener_coaches.php');
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

        const coaches = await response.json();

        if (coaches.length === 0) {
            gallery.innerHTML = '<p style="color: #a0a0a0;">No hay perfiles de coaches para mostrar.</p>';
            return;
        }

        gallery.innerHTML = '';
        coaches.forEach(coach => {
            const eventCard = document.createElement('div');
            eventCard.className = 'event-card';

            eventCard.innerHTML = `
                <img src="${coach.ruta_img_perfil}" alt="${coach.nombre_coach}">
                <div class="event-card-content">
                    <h4>${coach.nombre_coach}</h4> 
                    <p>${coach.especialidad}</p>
                </div>
                <div class="event-card-actions">
                    <button class="btn-delete" data-id="${coach.id}">
                        <i class="fa-solid fa-trash"></i> Eliminar
                    </button>
                </div>
            `;
            gallery.appendChild(eventCard);
        });

    } catch (error) {
        console.error('Error al cargar coaches:', error);
        gallery.innerHTML = '<p style="color: #e74c3c;">Error al cargar los perfiles.</p>';
        Swal.fire({
            title: 'Error al Cargar Perfiles',
            text: 'No se pudieron cargar los perfiles de coaches. Revisa tu conexión.',
            icon: 'error',
            confirmButtonText: 'Aceptar',
            ...swalDark
        });
    }
}

function deleteCoach(id, button) {
    const eventCard = button.closest('.event-card');
    const data = { id: id };

    fetch('/sieteveintitres/php/admins/eliminar_coach.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                eventCard.remove();
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'Perfil de coach eliminado',
                    showConfirmButton: false,
                    timer: 2000,
                    ...swalDark
                });
                loadCoachesForSelect();
            } else {
                Swal.fire({
                    title: 'Error al Eliminar',
                    text: result.message,
                    icon: 'error',
                    confirmButtonText: 'Entendido',
                    ...swalDark
                });
            }
        })
        .catch(error => {
            console.error('Error en la solicitud:', error);
            Swal.fire({
                title: 'Error de Conexión',
                text: 'No se pudo eliminar el perfil.',
                icon: 'error',
                confirmButtonText: 'Aceptar',
                ...swalDark
            });
        });
}