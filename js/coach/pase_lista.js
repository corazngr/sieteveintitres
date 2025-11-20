document.addEventListener('DOMContentLoaded', () => {
    const classDetailsHeader = document.getElementById('class-details-header');
    const riderListContainer = document.getElementById('rider-list-container');
    const saveButton = document.getElementById('guardar-lista-btn');

    const params = new URLSearchParams(window.location.search);
    const idHorario = params.get('id_horario');

    if (!idHorario) {
        classDetailsHeader.textContent = "Error: No se especificó una clase.";
        Swal.fire({
            title: 'Error',
            text: 'No se especificó una clase. Vuelve a la página anterior.',
            icon: 'error',
            background: '#1a1a1a',
            color: '#fff'
        });
        return;
    }

    saveButton.innerHTML = '<i class="fa-solid fa-file-pdf"></i> Generar Reporte PDF';

    riderListContainer.addEventListener('click', async (e) => {
        const button = e.target.closest('.attendance-btn');
        if (!button) return;

        if (button.classList.contains('selected')) {
            return;
        }

        const optionsContainer = button.parentElement;
        const id_asistencia = button.dataset.asistenciaId;
        const estatus = button.dataset.status;

        optionsContainer.querySelectorAll('.attendance-btn').forEach(btn => {
            btn.classList.remove('selected');
        });

        button.classList.add('selected');

        try {
            const response = await fetch('/sieteveintitres/php/coaches/actualizar_estatus.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id_asistencia: id_asistencia, estatus: estatus })
            });
            const result = await response.json();

            if (!result.success) {
                button.classList.remove('selected');
                console.error('Error al guardar asistencia:', result.message);
                Swal.fire({
                    title: 'Error al Guardar',
                    text: result.message,
                    icon: 'error',
                    confirmButtonText: 'Entendido',
                    background: '#1a1a1a',
                    color: '#fff'
                });
            }
        } catch (error) {
            console.error('Error de conexión:', error);
            Swal.fire({
                title: 'Error de Conexión',
                text: 'No se pudo guardar el cambio. Revisa tu conexión a internet.',
                icon: 'error',
                confirmButtonText: 'Aceptar',
                background: '#1a1a1a',
                color: '#fff'
            });
            button.classList.remove('selected');
        }
    });


    // CARGAR LOS DATOS DE LA CLASE Y LOS RIDERS 
    async function cargarLista() {
        try {
            const response = await fetch(`/sieteveintitres/php/coaches/lista_clase.php?id_horario=${idHorario}`);
            const result = await response.json();

            if (result.success) {
                const { clase_details, riders } = result.data;
                const fecha = new Date(clase_details.fecha + 'T' + clase_details.hora_inicio);
                const dia = fecha.toLocaleDateString('es-MX', { weekday: 'long' });
                const hora = fecha.toLocaleTimeString('es-MX', { hour: 'numeric', minute: '2-digit' });
                classDetailsHeader.innerHTML = `Clase: <strong>${clase_details.nombre_clase_especifico}</strong> | ${dia}, ${hora}`;
                riderListContainer.innerHTML = '';
                if (riders.length === 0) {
                    riderListContainer.innerHTML = '<li>No hay riders inscritos en esta clase.</li>';
                } else {
                    riders.forEach(rider => {
                        const isPresente = rider.estatus_asistencia === 'presente' ? 'selected' : '';
                        const isAusente = rider.estatus_asistencia === 'ausente' ? 'selected' : '';
                        const riderHTML = `
                            <li>
                                <span class="rider-bike-number">Bici #${rider.numero_bici}</span>
                                <span class="rider-name">${rider.nombre_rider}</span>
                
                                <div class="attendance-options">
                                    <button class="attendance-btn present ${isPresente}" data-asistencia-id="${rider.id_asistencia}" data-status="presente">
                                        <i class="fa-solid fa-circle-check"></i>
                                    </button>
                                    <button class="attendance-btn absent ${isAusente}" data-asistencia-id="${rider.id_asistencia}" data-status="ausente">
                                        <i class="fa-solid fa-circle-xmark"></i>
                                    </button>
                                </div>
                            </li>
                        `;
                        riderListContainer.insertAdjacentHTML('beforeend', riderHTML);
                    });
                }
            } else {
                classDetailsHeader.textContent = `Error: ${result.message}`;
                Swal.fire({
                    title: 'Error al Cargar la Lista',
                    text: result.message,
                    icon: 'error',
                    confirmButtonText: 'Aceptar',
                    background: '#1a1a1a',
                    color: '#fff'
                });
            }
        } catch (error) {
            console.error('Error de conexión al cargar la lista:', error);
            classDetailsHeader.textContent = 'Error de conexión al cargar la lista.';
            Swal.fire({
                title: 'Error de Conexión',
                text: 'No se pudo cargar la lista. Revisa tu conexión a internet.',
                icon: 'error',
                confirmButtonText: 'Aceptar',
                background: '#1a1a1a',
                color: '#fff'
            });
        }
    }

    saveButton.addEventListener('click', () => {
        console.log("Generando PDF para el horario:", idHorario);
        window.location.href = `/sieteveintitres/php/coaches/reporte_asistencia.php?id_horario=${idHorario}`;

        saveButton.disabled = true;
        saveButton.innerHTML = '<i class="fa-solid fa-check"></i> PDF Generado';

        setTimeout(() => {
            saveButton.disabled = false;
            saveButton.innerHTML = '<i class="fa-solid fa-file-pdf"></i> Generar Reporte PDF';
        }, 3000);
    });

    cargarLista();
});