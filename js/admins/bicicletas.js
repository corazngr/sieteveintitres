document.addEventListener('DOMContentLoaded', function () {
    // --- SELECTORES ---
    const bikesGrid = document.getElementById('bikesGridContainer');
    const addBikeBtn = document.getElementById('addBikeBtn');
    const filterBtns = document.querySelectorAll('.filter-btn');

    // Modales
    const editBikeModal = document.getElementById('editBikeModal');
    const addMaintenanceModal = document.getElementById('addMaintenanceModal');
    const historyModal = document.getElementById('historyModal');

    let allBikesData = [];

    const swalDark = {
        background: '#1a1a1a',
        color: '#fff'
    };

    // Lógica para abrir/cerrar menú en móvil
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const sidebar = document.querySelector('.sidebar');

    // Crear un overlay para cerrar el menú al hacer clic afuera
    const sidebarOverlay = document.createElement('div');
    sidebarOverlay.className = 'sidebar-overlay';
    document.body.appendChild(sidebarOverlay);

    // Estilos rápidos para el overlay del sidebar (puedes ponerlos en CSS)
    sidebarOverlay.style.position = 'fixed';
    sidebarOverlay.style.top = '0';
    sidebarOverlay.style.left = '0';
    sidebarOverlay.style.width = '100%';
    sidebarOverlay.style.height = '100%';
    sidebarOverlay.style.background = 'rgba(0,0,0,0.5)';
    sidebarOverlay.style.zIndex = '1000';
    sidebarOverlay.style.display = 'none';

    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', () => {
            sidebar.classList.toggle('active');
            sidebarOverlay.style.display = sidebar.classList.contains('active') ? 'block' : 'none';
        });
    }

    // Cerrar al hacer clic fuera
    sidebarOverlay.addEventListener('click', () => {
        sidebar.classList.remove('active');
        sidebarOverlay.style.display = 'none';
    });

    // --- FUNCIÓN PRINCIPAL DE CARGA ---
    async function fetchAndRenderBikes() {
        try {
            const response = await fetch('/sieteveintitres/php/admins/gestionar_bicicletas.php');
            const result = await response.json();
            if (!result.success) throw new Error('No se pudieron cargar los datos.');

            allBikesData = result.bicicletas;
            renderBikes(allBikesData);
            updateFilterCounts(result.counts);
        } catch (error) {
            console.error(error);
            bikesGrid.innerHTML = '<p>Error al cargar las bicicletas.</p>';
            Swal.fire({
                title: 'Error al Cargar',
                text: 'No se pudieron cargar las bicicletas. Revisa tu conexión.',
                icon: 'error',
                confirmButtonText: 'Aceptar',
                ...swalDark
            });
        }
    }

    // --- FUNCIONES DE RENDERIZADO ---
    function renderBikes(bikes) {
        bikesGrid.innerHTML = '';
        if (bikes.length === 0) {
            bikesGrid.innerHTML = '<p class="empty-message">No hay bicicletas que coincidan con el filtro.</p>';
            return;
        }
        bikes.forEach(bike => {
            const statusClass = bike.estado.toLowerCase().replace(/ /g, '-');
            const ultimoServicio = bike.ultimo_servicio ? new Date(bike.ultimo_servicio + 'T00:00:00').toLocaleDateString('es-MX') : 'N/A';

            const card = document.createElement('div');
            card.className = `bike-card status-${statusClass}`;
            card.dataset.id = bike.id_bicicleta;
            card.innerHTML = `
                <div class="card-header">
                    <h3>Bicicleta #${String(bike.id_bicicleta).padStart(2, '0')}</h3>
                    <span class="status-badge status-${statusClass}">${bike.estado}</span>
                </div>
                <div class="card-body">
                    <i class="fa-solid fa-bicycle bike-icon"></i>
                    <p><strong>Último Servicio:</strong> ${ultimoServicio}</p>
                    <p class="bike-description">${bike.descripcion || ''}</p>
                </div>
                <div class="card-actions">
                    <button class="action-btn-card maintenance">Registrar Mantenimiento</button>
                    <button class="action-btn-card change-status">Cambiar Estado</button>
                    <button class="action-btn-card view-history">Ver Historial</button>
                </div>
            `;
            bikesGrid.appendChild(card);
        });
    }

    function updateFilterCounts(counts) {
        document.querySelector('[data-filter="all"]').textContent = `Todas (${counts.all})`;
        document.querySelector('[data-filter="status-available"]').textContent = `Disponibles (${counts.Disponible})`;
        document.querySelector('[data-filter="status-maintenance"]').textContent = `En Mantenimiento (${counts.Mantenimiento})`;
        document.querySelector('[data-filter="status-out-of-service"]').textContent = `Fuera de Servicio (${counts['Fuera de Servicio']})`;
    }

    // --- LÓGICA DE MODALES ---
    function closeModal(modal) { modal.classList.remove('visible'); }
    function openModal(modal) { modal.classList.add('visible'); }

    [editBikeModal, addMaintenanceModal, historyModal].forEach(modal => {
        modal.querySelectorAll('.close-btn, .btn-secondary').forEach(btn => {
            btn.addEventListener('click', () => closeModal(modal));
        });
    });

    // --- LÓGICA DE ACCIONES ---

    // 1. Añadir nueva bicicleta
    addBikeBtn.addEventListener('click', async () => {
        const confirmation = await Swal.fire({
            title: '¿Añadir Bici?',
            text: '¿Deseas añadir una nueva bicicleta al inventario? Se creará con el siguiente número disponible y en estado "Disponible".',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, añadir',
            cancelButtonText: 'Cancelar',
            ...swalDark
        });

        if (!confirmation.isConfirmed) {
            return;
        }

        try {
            const response = await fetch('/sieteveintitres/php/admins/crear_bici.php', { method: 'POST' });
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
                fetchAndRenderBikes();
            } else {
                Swal.fire({
                    title: 'Error',
                    text: result.message,
                    icon: 'error',
                    confirmButtonText: 'Entendido',
                    ...swalDark
                });
            }
        } catch (error) {
            console.error('Error al añadir la bicicleta:', error);
            Swal.fire({
                title: 'Error de Conexión',
                text: 'No se pudo añadir la bicicleta.',
                icon: 'error',
                confirmButtonText: 'Aceptar',
                ...swalDark
            });
        }
    });

    // 2. Eventos en las tarjetas
    bikesGrid.addEventListener('click', async e => {
        const card = e.target.closest('.bike-card');
        if (!card) return;
        const bikeId = card.dataset.id;
        const bikeData = allBikesData.find(b => b.id_bicicleta == bikeId);

        if (e.target.matches('.change-status')) {
            document.getElementById('editBikeModalTitle').textContent = `Estado de Bicicleta #${String(bikeId).padStart(2, '0')}`;
            document.getElementById('editBikeForm').reset();
            document.getElementById('editBikeId').value = bikeId;
            document.getElementById('editBikeStatus').value = bikeData.estado.replace(/ /g, '-').toLowerCase(); // Convierte 'Fuera de Servicio' a 'fuera-de-servicio'
            document.getElementById('editBikeNotes').value = bikeData.descripcion || '';
            openModal(editBikeModal);
        }

        if (e.target.matches('.maintenance')) {
            document.getElementById('maintenanceModalTitle').textContent = `Registrar Mantenimiento Bici #${String(bikeId).padStart(2, '0')}`;
            document.getElementById('maintenanceForm').reset();
            document.getElementById('maintenanceBikeId').value = bikeId;
            document.getElementById('maintenanceDate').valueAsDate = new Date();
            openModal(addMaintenanceModal);
        }

        if (e.target.matches('.view-history')) {
            document.getElementById('historyModalTitle').textContent = `Historial de Bicicleta #${String(bikeId).padStart(2, '0')}`;
            const historyBody = document.getElementById('historyTableBody');
            historyBody.innerHTML = '<tr><td colspan="3">Cargando historial...</td></tr>';
            openModal(historyModal);

            const response = await fetch(`/sieteveintitres/php/admins/historial_mantenimientos.php?id=${bikeId}`);
            const result = await response.json();

            historyBody.innerHTML = '';
            if (result.success && result.data.length > 0) {
                result.data.forEach(item => {
                    historyBody.innerHTML += `
                        <tr>
                            <td>${new Date(item.fecha + 'T00:00:00').toLocaleDateString('es-MX')}</td>
                            <td>${item.descripcion}</td>
                            <td>${item.responsable}</td>
                        </tr>
                    `;
                });
            } else {
                historyBody.innerHTML = '<tr><td colspan="3">No hay registros de mantenimiento.</td></tr>';
            }
        }
    });

    // 3. Guardar cambios del estado de la bici
    document.getElementById('saveBikeChangesBtn').addEventListener('click', async () => {
        const formData = new FormData();
        formData.append('id_bicicleta', document.getElementById('editBikeId').value);
        formData.append('estado', document.getElementById('editBikeStatus').value);
        formData.append('descripcion', document.getElementById('editBikeNotes').value);

        try {
            const response = await fetch('/sieteveintitres/php/admins/crear_bici.php', { method: 'POST', body: formData });
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
                closeModal(editBikeModal);
                fetchAndRenderBikes();
            } else {
                Swal.fire({
                    title: 'Error',
                    text: result.message,
                    icon: 'error',
                    confirmButtonText: 'Entendido',
                    ...swalDark
                });
            }
        } catch (error) {
            console.error('Error al guardar estado:', error);
            Swal.fire({
                title: 'Error de Conexión',
                text: 'No se pudieron guardar los cambios.',
                icon: 'error',
                confirmButtonText: 'Aceptar',
                ...swalDark
            });
        }
    });

    // 4. Guardar nuevo registro de mantenimiento
    document.getElementById('saveMaintenanceBtn').addEventListener('click', async () => {
        // Obtenemos los valores de los campos del formulario
        const bikeId = document.getElementById('maintenanceBikeId').value;
        const maintenanceDate = document.getElementById('maintenanceDate').value;
        const maintenanceNotes = document.getElementById('maintenanceNotes').value;
        const maintenanceResponsible = document.getElementById('maintenanceResponsible').value;

        // Validación
        if (!bikeId || !maintenanceDate || !maintenanceNotes || !maintenanceResponsible) {
            Swal.fire({
                title: 'Campos Incompletos',
                text: 'Por favor, completa todos los campos del formulario.',
                icon: 'warning',
                confirmButtonText: 'Entendido',
                ...swalDark
            });
            return;
        }

        const formData = new FormData();
        formData.append('id_bicicleta', bikeId);
        formData.append('fecha', maintenanceDate);
        formData.append('descripcion', maintenanceNotes);
        formData.append('responsable', maintenanceResponsible);

        try {
            const response = await fetch('/sieteveintitres/php/admins/registrar_mantenimiento.php', { method: 'POST', body: formData });
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
                closeModal(addMaintenanceModal);
                fetchAndRenderBikes();
            } else {
                Swal.fire({
                    title: 'Error',
                    text: result.message,
                    icon: 'error',
                    confirmButtonText: 'Entendido',
                    ...swalDark
                });
            }
        } catch (error) {
            console.error('Error al registrar mantenimiento:', error);
            Swal.fire({
                title: 'Error de Conexión',
                text: 'No se pudo registrar el mantenimiento.',
                icon: 'error',
                confirmButtonText: 'Aceptar',
                ...swalDark
            });
        }
    });

    // --- Lógica de filtros ---
    filterBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            filterBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            const filterValue = this.dataset.filter;
            let dbStatus = '';
            switch (filterValue) {
                case 'status-available': dbStatus = 'Disponible'; break;
                case 'status-maintenance': dbStatus = 'Mantenimiento'; break;
                case 'status-out-of-service': dbStatus = 'Fuera de Servicio'; break;
                default: dbStatus = 'all';
            }
            const filteredBikes = (dbStatus === 'all')
                ? allBikesData
                : allBikesData.filter(bike => bike.estado === dbStatus);
            renderBikes(filteredBikes);
        });
    });

    // --- CARGA INICIAL ---
    fetchAndRenderBikes();
});