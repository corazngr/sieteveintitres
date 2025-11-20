document.addEventListener('DOMContentLoaded', function () {
    const currentWeekDisplay = document.getElementById('currentWeekDisplay');
    const prevWeekBtn = document.getElementById('prevWeekBtn');
    const nextWeekBtn = document.getElementById('nextWeekBtn');
    const calendarGrid = document.querySelector('.calendar-grid');
    const modal = document.getElementById('addClassModal');
    const openModalBtn = document.getElementById('openModalBtn');
    const closeModalBtns = modal.querySelectorAll('.close-btn, .btn-secondary');
    const saveClassBtn = modal.querySelector('.btn-primary');
    const classForm = modal.querySelector('.modal-form');
    const coachSelect = document.getElementById('classCoach');
    const viewRidersModal = document.getElementById('viewRidersModal');
    const viewRidersTitle = document.getElementById('viewRidersTitle');
    const ridersListContainer = document.getElementById('ridersListContainer');
    const closeViewRidersBtns = viewRidersModal.querySelectorAll('.close-btn, .btn-secondary');
    const classIdInput = document.getElementById('classId');

    const swalDark = {
        background: '#1a1a1a',
        color: '#fff'
    };

    // LÓGICA PARA CELULARES 
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

    const days = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
    days.forEach((dayName, index) => {
        const dayColumn = document.getElementById(`day-${index + 1}`);
        if (dayColumn) {
            dayColumn.insertAdjacentHTML('afterbegin', `<div class="mobile-day-title">${dayName}</div>`);
        }
    });

    // --- FUNCIÓN PRINCIPAL PARA CARGAR Y RENDERIZAR ---
    async function fetchAndRenderCalendar(date = null) {
        const url = date ? `/sieteveintitres/php/admins/gestionar_clases.php?date=${date}` : '/sieteveintitres/php/admins/gestionar_clases.php';

        try {
            const response = await fetch(url);
            const result = await response.json();

            if (!result.success) throw new Error(result.message);

            // 1. Actualizar controles de navegación
            currentWeekDisplay.textContent = result.week_display;
            prevWeekBtn.dataset.date = result.prev_week_date;
            nextWeekBtn.dataset.date = result.next_week_date;
            currentWeekDisplay.dataset.currentDate = result.current_week_date;

            // 2. Limpiar calendario
            document.querySelectorAll('.calendar-day').forEach(day => day.innerHTML = '');

            // 3. Renderizar clases
            result.clases.forEach(clase => {
                let dayOfWeek = new Date(clase.fecha + 'T00:00:00').getDay();
                if (dayOfWeek === 0) dayOfWeek = 7;

                const dayColumn = document.getElementById(`day-${dayOfWeek}`);
                if (dayColumn) {
                    dayColumn.innerHTML += createClassCardHTML(clase);
                }
            });

            // 4. Rellenar select de coaches
            coachSelect.innerHTML = '<option value="" disabled selected>Selecciona un coach</option>';
            result.coaches.forEach(coach => {
                coachSelect.innerHTML += `<option value="${coach.id_coach}">${coach.nombre_coach}</option>`;
            });

        } catch (error) {
            console.error('Error al cargar el calendario:', error);
            currentWeekDisplay.textContent = 'Error al cargar';
            Swal.fire({
                title: 'Error al Cargar Calendario',
                text: 'No se pudieron cargar las clases. Revisa tu conexión.',
                icon: 'error',
                confirmButtonText: 'Aceptar',
                ...swalDark
            });
        }
    }

    // --- FUNCIÓN PARA CREAR EL HTML DE UNA CLASE ---
    function createClassCardHTML(clase) {
        const ocupacion = clase.cupo_maximo > 0 ? (clase.reservados / clase.cupo_maximo) * 100 : 0;
        let progress_class = '';
        if (ocupacion >= 100) progress_class = 'full';
        else if (ocupacion >= 90) progress_class = 'warning';

        const startTime = new Date(`1970-01-01T${clase.hora_inicio}`).toLocaleTimeString('es-MX', { hour: 'numeric', minute: '2-digit', hour12: true });

        return `
            <div class="class-card" data-id="${clase.id_horario}">
                <span class="class-time">${startTime}</span>
                <h4 class="class-title">${clase.nombre_clase_especifico}</h4>
                <span class="class-coach"><i class="fa-solid fa-user-shield"></i> ${clase.nombre_coach}</span>
                <div class="class-occupancy">
                    <span>${clase.reservados} / ${clase.cupo_maximo} Reservados</span>
                    <div class="progress-bar">
                        <div class="progress-fill ${progress_class}" style="width: ${ocupacion}%;"></div>
                    </div>
                </div>
                <div class="card-actions">
                    <button class="action-btn-card view">Ver Lista</button>
                    <button class="action-btn-card edit"><i class="fa-solid fa-pencil"></i></button>
                    <button class="action-btn-card delete"><i class="fa-solid fa-trash"></i></button>
                </div>
            </div>
        `;
    }

    // --- MANEJO DEL MODAL ---
    openModalBtn.addEventListener('click', () => {
        classForm.reset();
        classIdInput.value = '';
        modal.querySelector('h2').textContent = 'Programar Nueva Clase';
        saveClassBtn.textContent = 'Guardar Clase';
        classForm.style.opacity = '1';
        modal.classList.add('visible');
    });

    // (Las otras dos líneas de 'closeViewRidersBtns' y 'closeModalBtns' se quedan igual)
    closeViewRidersBtns.forEach(btn => btn.addEventListener('click', () => viewRidersModal.classList.remove('visible')));
    closeModalBtns.forEach(btn => btn.addEventListener('click', () => modal.classList.remove('visible')));

    // --- GUARDAR NUEVA CLASE ---
    saveClassBtn.addEventListener('click', async () => {
        const id_horario = classIdInput.value;
        const isEditing = id_horario > 0;

        const url = isEditing ? '/sieteveintitres/php/admins/actualizar_clase.php' : '/sieteveintitres/php/admins/crear_clase.php';

        const formData = new FormData(classForm);
        formData.append('nombre', document.getElementById('className').value);
        formData.append('fecha', document.getElementById('classDate').value);
        formData.append('hora_inicio', document.getElementById('classTime').value);
        formData.append('cupo', document.getElementById('classCapacity').value);
        formData.append('duracion', document.getElementById('classDuration').value);

        if (isEditing) {
            formData.append('id_horario', id_horario);
        }

        try {
            const response = await fetch(url, {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.success) {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: result.message,
                    showConfirmButton: false,
                    timer: 2000,
                    ...swalDark
                });
                modal.classList.remove('visible');
                classForm.reset();
                classIdInput.value = '';
                modal.querySelector('h2').textContent = 'Programar Nueva Clase';
                saveClassBtn.textContent = 'Guardar Clase';
                fetchAndRenderCalendar(currentWeekDisplay.dataset.currentDate);
            } else {
                Swal.fire({
                    title: 'Error al Guardar',
                    text: result.message,
                    icon: 'error',
                    confirmButtonText: 'Entendido',
                    ...swalDark
                });
            }
        } catch (error) {
            console.error('Error:', error);
            Swal.fire({
                title: 'Error de Conexión',
                text: 'No se pudo guardar la clase.',
                icon: 'error',
                confirmButtonText: 'Aceptar',
                ...swalDark
            });
        }
    });

    // --- ELIMINAR CLASE ---
    calendarGrid.addEventListener('click', async function (e) {
        if (e.target.closest('.delete')) {
            const card = e.target.closest('.class-card');
            const id_horario = card.dataset.id;
            const className = card.querySelector('.class-title').textContent;

            const confirmation = await Swal.fire({
                title: `¿Eliminar Clase "${className}"?`,
                text: "Se cancelarán todas las reservaciones asociadas. ¡Esta acción no se puede deshacer!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#d33', // Color rojo para el botón de eliminar
                ...swalDark
            });

            if (confirmation.isConfirmed) {
                const formData = new FormData();
                formData.append('id_horario', id_horario);

                try {
                    const response = await fetch('/sieteveintitres/php/admins/eliminar_clase.php', {
                        method: 'POST',
                        body: formData
                    });
                    const result = await response.json();

                    if (result.success) {
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'success',
                            title: result.message,
                            showConfirmButton: false,
                            timer: 2000,
                            ...swalDark
                        });
                        fetchAndRenderCalendar(currentWeekDisplay.dataset.currentDate);
                    } else {
                        Swal.fire({
                            title: 'Error al Eliminar',
                            text: result.message,
                            icon: 'error',
                            confirmButtonText: 'Entendido',
                            ...swalDark
                        });
                    }
                } catch (error) {
                    console.error('Error al eliminar clase:', error);
                    Swal.fire({
                        title: 'Error de Conexión',
                        text: 'No se pudo eliminar la clase.',
                        icon: 'error',
                        confirmButtonText: 'Aceptar',
                        ...swalDark
                    });
                }
            }
        }

        if (e.target.closest('.view')) {
            const card = e.target.closest('.class-card');
            const id_horario = card.dataset.id;
            const className = card.querySelector('.class-title').textContent.trim();

            viewRidersTitle.textContent = `Riders en "${className}"`;
            ridersListContainer.innerHTML = '<p>Cargando lista...</p>';
            viewRidersModal.classList.add('visible');

            try {
                const response = await fetch(`/sieteveintitres/php/admins/lista_clase.php?id_horario=${id_horario}`);
                const result = await response.json();

                if (result.success && result.data.length > 0) {
                    ridersListContainer.innerHTML = ''; // Limpiar
                    result.data.forEach(rider => {
                        const defaultAvatar = '/sieteveintitres/images/icono-default.jpg';
                        ridersListContainer.innerHTML += `
                    <div class="rider-item">
                        <img src="${rider.foto_rider || defaultAvatar}" alt="Avatar" class="rider-avatar">
                        <div class="rider-info">
                            <span class="rider-name">${rider.nombre_rider}</span>
                            <span class="rider-bike">
                                <i class="fa-solid fa-bicycle"></i> Bici #${rider.id_bicicleta}
                            </span>
                        </div>
                    </div>
                `;
                    });
                } else {
                    ridersListContainer.innerHTML = '<p>Aún no hay riders en esta clase.</p>';
                }
            } catch (error) {
                ridersListContainer.innerHTML = '<p>Error al cargar la lista.</p>';
            }
        }

        if (e.target.closest('.edit')) {
            const card = e.target.closest('.class-card');
            const id_horario = card.dataset.id;

            // 1. Mostrar modal y cambiar títulos
            classIdInput.value = id_horario;
            modal.querySelector('h2').textContent = 'Editar Clase';
            saveClassBtn.textContent = 'Guardar Cambios';
            modal.classList.add('visible');
            classForm.reset();
            classForm.style.opacity = '0.5';

            // 2. Hacer FETCH al nuevo PHP para obtener los detalles
            try {
                const response = await fetch(`/sieteveintitres/php/admins/obtener_detalle_clase.php?id_horario=${id_horario}`);
                const result = await response.json();

                if (result.success) {
                    const clase = result.data;

                    // 3. Rellenar el formulario con los datos obtenidos
                    document.getElementById('className').value = clase.nombre_clase_especifico;
                    document.getElementById('classDate').value = clase.fecha;
                    document.getElementById('classTime').value = clase.hora_inicio.substring(0, 5);
                    document.getElementById('classCapacity').value = clase.cupo_maximo;
                    document.getElementById('classDuration').value = clase.duracion_calculada;
                    document.getElementById('classCoach').value = clase.id_coach;

                    classForm.style.opacity = '1'; 

                } else {
                    Swal.fire({
                        title: 'Error al Cargar Datos',
                        text: result.message,
                        icon: 'error',
                        confirmButtonText: 'Aceptar',
                        ...swalDark
                    });
                    modal.classList.remove('visible');
                }
            } catch (error) {
                console.error('Error en fetch:', error);
                Swal.fire({
                    title: 'Error de Conexión',
                    text: 'No se pudieron cargar los detalles de la clase.',
                    icon: 'error',
                    confirmButtonText: 'Aceptar',
                    ...swalDark
                });
                modal.classList.remove('visible');
            }
        }
    });

    // --- NAVEGACIÓN DE SEMANAS ---
    prevWeekBtn.addEventListener('click', (e) => {
        e.preventDefault();
        fetchAndRenderCalendar(e.target.dataset.date);
    });
    nextWeekBtn.addEventListener('click', (e) => {
        e.preventDefault();
        fetchAndRenderCalendar(e.target.dataset.date);
    });

    // --- CARGA INICIAL ---
    const urlParams = new URLSearchParams(window.location.search);
    const initialDate = urlParams.get('date');
    fetchAndRenderCalendar(initialDate);
});