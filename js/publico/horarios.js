document.addEventListener('DOMContentLoaded', () => {
    const scheduleBody = document.querySelector('.schedule-table tbody');
    const weekRangeDisplay = document.getElementById('week-range-display');
    const tableHeaders = document.querySelectorAll('.schedule-table thead th');

    scheduleBody.addEventListener('click', function (event) {
        if (event.target.classList.contains('btn-reservar')) {
            event.preventDefault();

            const button = event.target;
            const destinationUrl = button.dataset.url;
            const classDateTimeStr = button.dataset.datetime;

            const classTime = new Date(classDateTimeStr);
            const now = new Date();

            if (classTime < now) {
                Swal.fire({
                    title: 'Clase Finalizada',
                    text: 'Esta clase ya ha finalizado y no puede ser reservada.',
                    icon: 'warning',
                    confirmButtonText: 'Entendido',
                    background: '#1a1a1a', 
                    color: '#fff'     
                });
                button.textContent = 'Finalizada';
                button.disabled = true;
                return;
            }

            fetch('/sieteveintitres/php/auth/verificar_sesion.php')
                .then(response => response.json())
                .then(session => {

                    if (!session.loggedIn) {
                        Swal.fire({
                            title: 'Inicia Sesión',
                            text: 'Debes iniciar sesión para reservar una clase.',
                            icon: 'info',
                            confirmButtonText: 'Iniciar Sesión',
                            background: '#1a1a1a',
                            color: '#fff'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location.href = '/sieteveintitres/html/publico/iniciosesion.html';
                            }
                        });

                    } else if (!session.tieneMembresiaActiva) {
                        Swal.fire({
                            title: '¡Necesitas una Membresía!',
                            text: '¡Necesitas una membresía activa para reservar! Adquiere una y únete a la rodada.',
                            icon: 'warning',
                            confirmButtonText: 'Ver Membresías',
                            background: '#1a1a1a',
                            color: '#fff'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location.href = '/sieteveintitres/html/publico/membresias.html';
                            }
                        });

                    } else {
                        window.location.href = destinationUrl;
                    }
                })
                .catch(error => {
                    console.error('Error al verificar la sesión:', error);

                    Swal.fire({
                        title: 'Error de Conexión',
                        text: 'Hubo un problema al verificar tu sesión. Inténtalo de nuevo.',
                        icon: 'error',
                        confirmButtonText: 'Aceptar',
                        background: '#1a1a1a',
                        color: '#fff'
                    });
                });
        }
    });

    function updateHeaders(weekInfo) {
        if (!weekInfo || !weekRangeDisplay || tableHeaders.length < 7) return;

        weekRangeDisplay.textContent = weekInfo.range_string;

        weekInfo.days.forEach((day, index) => {
            if (tableHeaders[index + 1]) {
                tableHeaders[index + 1].innerHTML = `${day.name} <span class="header-date">${day.date}</span>`;
            }
        });
    }

    function renderSchedule(data) {
        scheduleBody.innerHTML = '';
        const horas = Object.keys(data);

        if (horas.length === 0) {
            scheduleBody.innerHTML = `<tr><td colspan="7" style="text-align:center; padding: 20px;">No hay clases programadas para esta semana.</td></tr>`;
            initializeFilters();
            return;
        }

        horas.forEach(hora => {
            const row = document.createElement('tr');
            const horaAmPm = new Date(`1970-01-01T${hora}:00`).toLocaleTimeString('es-MX', {
                hour: '2-digit',
                minute: '2-digit',
                hour12: true
            });

            let rowHtml = `<td>${horaAmPm}</td>`;

            for (let i = 1; i <= 6; i++) {
                const clase = data[hora] ? data[hora][i] : null;
                if (clase) {
                    rowHtml += `
                        <td>
                            <div class="class-info">
                                <span class="class-name">${clase.nombre_clase}</span>
                                <span class="class-coach">con ${clase.coach}</span>
                                <button class="btn-reservar" 
                                        data-url="reservarbici.html?id_horario=${clase.id_horario}"
                                        data-datetime="${clase.fecha}T${clase.hora_inicio}">Reservar</button>
                            </div>
                        </td>`;
                } else {
                    rowHtml += '<td></td>';
                }
            }
            row.innerHTML = rowHtml;
            scheduleBody.appendChild(row);
        });

        initializeFilters();
    }

    fetch('/sieteveintitres/php/publico/horarios.php')
        .then(response => {
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }
            return response.json();
        })
        .then(apiResponse => {
            if (apiResponse.status === 'success') {
                updateHeaders(apiResponse.week_info);
                renderSchedule(apiResponse.data);
            } else {
                weekRangeDisplay.textContent = 'Error al cargar';
                scheduleBody.innerHTML = `<tr><td colspan="7" style="text-align:center; padding: 20px;">${apiResponse.message}</td></tr>`;
            }
        })
        .catch(error => {
            console.error('Error al cargar los horarios:', error);
            weekRangeDisplay.textContent = 'Error de conexión';
            scheduleBody.innerHTML = `<tr><td colspan="7" style="text-align:center; padding: 20px;">No se pudo conectar con el servidor.</td></tr>`;
        });

    function initializeFilters() {
        // --- LÓGICA DE FILTROS ---
        const dayFilter = document.getElementById('day-filter');
        const coachFilter = document.getElementById('coach-filter');
        const timeFilter = document.getElementById('time-filter');
        const resetBtn = document.getElementById('reset-filters');

        if (!dayFilter || !coachFilter || !timeFilter || !resetBtn) {
            console.warn("No se encontraron todos los elementos de filtro en la página.");
            return;
        }

        function populateFilters() {
            const coaches = new Set();
            const times = new Set();

            coachFilter.innerHTML = '<option value="todos">Todos los coaches</option>';
            timeFilter.innerHTML = '<option value="todos">Todas las horas</option>';

            scheduleBody.querySelectorAll('.class-info .class-coach').forEach(info => {
                coaches.add(info.textContent.trim());
            });

            scheduleBody.querySelectorAll('tr td:first-child').forEach(cell => {
                const timeValue = cell.textContent.trim();
                if (timeValue) times.add(timeValue);
            });

            coaches.forEach(coach => {
                coachFilter.innerHTML += `<option value="${coach}">${coach}</option>`;
            });

            times.forEach(time => {
                timeFilter.innerHTML += `<option value="${time}">${time}</option>`;
            });
        }

        function filterSchedule() {
            const selectedDay = dayFilter.value;
            const selectedCoach = coachFilter.value;
            const selectedTime = timeFilter.value;

            scheduleBody.querySelectorAll('tr').forEach(row => {
                const rowTime = row.firstElementChild.textContent.trim();
                let rowHasVisibleClasses = false;

                row.querySelectorAll('td:not(:first-child)').forEach((cell, index) => {
                    const classInfo = cell.querySelector('.class-info');
                    if (classInfo) {
                        const coachName = classInfo.querySelector('.class-coach').textContent.trim();
                        const dayMatch = selectedDay === 'todos' || selectedDay == (index + 1);
                        const coachMatch = selectedCoach === 'todos' || selectedCoach === coachName;
                        const timeMatch = selectedTime === 'todos' || selectedTime === rowTime;

                        cell.style.visibility = (dayMatch && coachMatch && timeMatch) ? 'visible' : 'hidden';
                        if (cell.style.visibility === 'visible') {
                            rowHasVisibleClasses = true;
                        }
                    }
                });
                row.style.display = rowHasVisibleClasses ? '' : 'none';
            });
        }

        dayFilter.addEventListener('change', filterSchedule);
        coachFilter.addEventListener('change', filterSchedule);
        timeFilter.addEventListener('change', filterSchedule);

        resetBtn.addEventListener('click', () => {
            dayFilter.value = 'todos';
            coachFilter.value = 'todos';
            timeFilter.value = 'todos';
            filterSchedule();
        });

        populateFilters();
    }
});