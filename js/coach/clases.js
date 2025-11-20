document.addEventListener('DOMContentLoaded', () => {
    const dayColumnMap = {
        1: 'lunes-column',
        2: 'martes-column',
        3: 'miercoles-column',
        4: 'jueves-column',
        5: 'viernes-column',
        6: 'sabado-column',
        0: 'domingo-column'
    };

    async function cargarHorario() {
        try {
            const response = await fetch('/sieteveintitres/php/coaches/horario_semanal.php');
            const result = await response.json();

            if (result.success && result.data.length > 0) {
                result.data.forEach(clase => {
                    // Convertimos la fecha de la BD a un objeto Date de JavaScript
                    const fechaClase = new Date(clase.fecha + 'T00:00:00');
                    const diaDeLaSemana = fechaClase.getDay(); // 0-6

                    // Buscamos el ID de la columna que corresponde a ese día
                    const columnId = dayColumnMap[diaDeLaSemana];
                    const columnElement = document.getElementById(columnId);

                    if (columnElement) {
                        // Formateamos la hora para mostrarla en formato AM/PM
                        const hora = new Date(`1970-01-01T${clase.hora_inicio}`).toLocaleTimeString('es-MX', {
                            hour: 'numeric',
                            minute: '2-digit',
                            hour12: true
                        });

                        // Creamos el HTML para el bloque de la clase
                        const classBlockHTML = `
                            <div class="class-block">
                                <span class="time"><i class="fa-solid fa-clock"></i> ${hora}</span>
                                <span class="class-name">${clase.nombre_clase_especifico || 'Clase de Spinning'}</span>
                                <span class="participants"><i class="fa-solid fa-users"></i> ${clase.inscritos}/${clase.cupo_maximo}</span>
                                <a href="/sieteveintitres/html/coaches/paselista.html?id_horario=${clase.id_horario}" class="btn-attendance">
                                    <i class="fa-solid fa-list-check"></i> Pase de lista
                                </a>
                            </div>
                        `;

                        // Añadimos el bloque de clase a su columna correspondiente
                        columnElement.insertAdjacentHTML('beforeend', classBlockHTML);
                    }
                });
            } else if (!result.success) {
                // --- ¡CAMBIO 1: Notificación de error controlado! ---
                Swal.fire({
                    title: 'Error al Cargar Horario',
                    text: result.message,
                    icon: 'error',
                    confirmButtonText: 'Aceptar',
                    background: '#1a1a1a', // Tema oscuro
                    color: '#fff'         // Texto blanco
                });
            }

        } catch (error) {
            console.error('Error de conexión:', error);
            // --- ¡CAMBIO 2: Notificación de error de conexión! ---
            Swal.fire({
                title: 'Error de Conexión',
                text: 'No se pudo conectar con el servidor para cargar el horario.',
                icon: 'error',
                confirmButtonText: 'Aceptar',
                background: '#1a1a1a',
                color: '#fff'
            });
        }
    }

    // Llamamos a la función principal para que se ejecute al cargar la página
    cargarHorario();
});