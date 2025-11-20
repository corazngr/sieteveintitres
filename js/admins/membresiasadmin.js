document.addEventListener('DOMContentLoaded', function () {
    // --- SELECTORES DE ELEMENTOS ---
    const typesContainer = document.getElementById('membershipTypesContainer');
    const activeTableBody = document.getElementById('activeMembershipsTableBody');
    const searchInput = document.getElementById('searchInput');

    // Selectores para el modal de Crear/Editar
    const createModal = document.getElementById('createMembershipModal');
    const createModalForm = createModal.querySelector('.modal-form');
    const createModalTitle = createModal.querySelector('h2');
    const saveBtn = createModal.querySelector('.btn-primary');
    const cancelCreateBtn = createModal.querySelector('.btn-secondary');
    const closeCreateBtn = createModal.querySelector('.close-btn');
    const membershipIdInput = document.getElementById('membershipId');

    // Selectores para el modal de Desactivar
    const deactivateModal = document.getElementById('deactivateMembershipModal');
    const deactivateNameSpan = document.getElementById('deactivateMembershipName');
    const confirmDeactivateBtn = deactivateModal.querySelector('.btn-danger');

    // Selectores para el modal de Activar
    const activateModal = document.getElementById('activateMembershipModal');
    const activateNameSpan = document.getElementById('activateMembershipName');
    const confirmActivateBtn = activateModal.querySelector('.btn-success');

    // Selectores para el modal de Eliminar
    const deleteModal = document.getElementById('deleteMembershipModal');
    const deleteNameSpan = document.getElementById('deleteMembershipName');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');

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

    // --- FUNCIÓN PRINCIPAL DE CARGA ---
    async function fetchAndRenderData() {
        try {
            const response = await fetch('/sieteveintitres/php/admins/gestionar_membresias.php');
            const result = await response.json();
            if (!result.success) throw new Error(result.message);

            renderMembershipTypes(result.tipos);
            renderActiveMemberships(result.activas);
        } catch (error) {
            console.error('Error al cargar datos:', error);
            typesContainer.innerHTML = '<p>Error al cargar tipos de membresía.</p>';
            activeTableBody.innerHTML = '<tr><td colspan="6">Error al cargar membresías activas.</td></tr>';
            Swal.fire({
                title: 'Error al Cargar Datos',
                text: 'No se pudieron cargar los datos de membresías. Revisa tu conexión.',
                icon: 'error',
                confirmButtonText: 'Aceptar',
                ...swalDark
            });
        }
    }

    // --- FUNCIONES DE RENDERIZADO ---
    function renderMembershipTypes(tipos) {
        typesContainer.innerHTML = '';
        if (!tipos || tipos.length === 0) {
            typesContainer.innerHTML = '<p>No hay tipos de membresía definidos.</p>';
            return;
        }
        tipos.forEach(tipo => {
            const isInactive = tipo.estatus === 'Inactivo';
            const featuresHTML = (tipo.caracteristicas || '').split('\n').filter(line => line.trim() !== '').map(line => {
                const isEnabled = line.startsWith('+');
                const text = line.substring(1).trim();
                const icon = isEnabled ? 'fa-circle-check' : 'fa-circle-xmark';
                const liClass = isEnabled ? '' : 'disabled';
                return `<li class="${liClass}"><i class="fa-solid ${icon}"></i> ${text}</li>`;
            }).join('');

            const cardHTML = `
                <div class="membership-card ${tipo.es_popular == 1 ? 'featured' : ''} ${isInactive ? 'inactive' : ''}" data-id="${tipo.id_tipo_membresia}">
                    ${tipo.es_popular == 1 ? '<span class="badge">Más Popular</span>' : ''}
                    <h3 class="card-title">${tipo.nombre}</h3>
                    <p class="card-price">$${parseFloat(tipo.precio).toLocaleString('es-MX')} <span class="price-period">${tipo.periodo}</span></p>
                    <p class="card-description">${tipo.descripcion}</p>
                    <ul class="card-features-list">${featuresHTML}</ul>
                    <div class="card-actions">
                    <button class="action-btn-card edit">Editar</button>
                        ${isInactive
                    ? `<button class="action-btn-card activate">Activar</button>`
                    : `<button class="action-btn-card delete">Desactivar</button>`
                }
                    <button class="action-btn-card permanent-delete">Eliminar</button> </div>
                    </div>
                </div>
            `;
            typesContainer.innerHTML += cardHTML;
        });
    }

    function renderActiveMemberships(activas) {
        activeTableBody.innerHTML = '';
        if (!activas || activas.length === 0) {
            activeTableBody.innerHTML = '<tr><td colspan="6" class="text-center">No hay membresías activas actualmente.</td></tr>';
            return;
        }
        activas.forEach(activa => {
            const statusClass = activa.estado === 'Activa' ? 'status-active' : 'status-expires-soon';
            const rowHTML = `
                <tr>
                    <td>${activa.nombre_rider}</td>
                    <td>${activa.nombre_tipo_membresia}</td>
                    <td>${new Date(activa.fecha_inicio + 'T00:00:00').toLocaleDateString('es-MX', { year: 'numeric', month: '2-digit', day: '2-digit' })}</td>
                    <td>${new Date(activa.fecha_fin + 'T00:00:00').toLocaleDateString('es-MX', { year: 'numeric', month: '2-digit', day: '2-digit' })}</td>
                    <td>${activa.clases_restantes != null ? activa.clases_restantes : 'N/A'}</td>
                    <td class="dias-restantes">${activa.dias_restantes} días</td> 
                    <td><span class="status-badge ${statusClass}">${activa.estado}</span></td>
                </tr>
            `;
            activeTableBody.innerHTML += rowHTML;
        });
    }

    // --- FILTRO DE TABLA ---
    searchInput.addEventListener('input', () => {
        const searchTerm = searchInput.value.toLowerCase();
        const rows = activeTableBody.querySelectorAll('tr');
        rows.forEach(row => {
            if (row.cells.length > 0) {
                const riderName = row.cells[0].textContent.toLowerCase();
                row.style.display = riderName.includes(searchTerm) ? '' : 'none';
            }
        });
    });

    // --- LÓGICA DE MODALES Y ACCIONES ---

    // Abrir modal para CREAR
    document.getElementById('openCreateModalBtn').addEventListener('click', () => {
        createModalForm.reset();
        membershipIdInput.value = '';
        createModalTitle.textContent = 'Crear Nueva Membresía';
        saveBtn.textContent = 'Crear Membresía';
        createModal.classList.add('visible');
    });

    // Cerrar el modal de Crear/Editar
    [cancelCreateBtn, closeCreateBtn].forEach(btn => {
        btn.addEventListener('click', () => createModal.classList.remove('visible'));
    });

    // Guardar (Crear o Editar)
    saveBtn.addEventListener('click', async () => {
        const id = membershipIdInput.value;
        const url = '/sieteveintitres/php/admins/crear_editar_membresia.php';

        const formData = new FormData();
        formData.append('id_tipo_membresia', id);
        formData.append('nombre', document.getElementById('membershipName').value);
        formData.append('precio', document.getElementById('membershipPrice').value);
        formData.append('periodo', document.getElementById('membershipPeriod').value);
        formData.append('descripcion', document.getElementById('membershipDescription').value);
        formData.append('caracteristicas', document.getElementById('membershipFeatures').value);
        formData.append('es_popular', document.getElementById('isFeatured').checked);

        try {
            const response = await fetch(url, { method: 'POST', body: formData });
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
                createModal.classList.remove('visible');
                fetchAndRenderData();
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
            console.error('Error al guardar:', error);
            Swal.fire({
                title: 'Error de Conexión',
                text: 'No se pudieron guardar los cambios.',
                icon: 'error',
                confirmButtonText: 'Aceptar',
                ...swalDark
            });
        }
    });

    // Lógica para los botones de las tarjetas (Editar, Activar, Desactivar)
    typesContainer.addEventListener('click', async e => {
        const card = e.target.closest('.membership-card');
        if (!card) return;
        const id = card.dataset.id;
        const nombre = card.querySelector('.card-title').textContent.trim();

        // EDITAR
        if (e.target.classList.contains('edit')) {
            try {
                const response = await fetch(`/sieteveintitres/php/admins/obtener_tipo_membresia.php?id=${id}`);
                const result = await response.json();
                if (!result.success) throw new Error(result.message);

                const data = result.data;
                membershipIdInput.value = data.id_tipo_membresia;
                document.getElementById('membershipName').value = data.nombre;
                document.getElementById('membershipPrice').value = data.precio;
                document.getElementById('membershipPeriod').value = data.periodo;
                document.getElementById('membershipDescription').value = data.descripcion;
                document.getElementById('membershipFeatures').value = data.caracteristicas;
                document.getElementById('isFeatured').checked = (data.es_popular == 1);

                createModalTitle.textContent = 'Editar Tipo de Membresía';
                saveBtn.textContent = 'Guardar Cambios';
                createModal.classList.add('visible');

            } catch (error) {
                Swal.fire({
                    title: 'Error al Cargar Datos',
                    text: 'No se pudieron obtener los datos de la membresía.',
                    icon: 'error',
                    confirmButtonText: 'Aceptar',
                    ...swalDark
                });
                console.error(error);
            }
        }

        // DESACTIVAR
        if (e.target.classList.contains('delete')) {
            deactivateNameSpan.textContent = nombre;
            confirmDeactivateBtn.dataset.id = id;
            deactivateModal.classList.add('visible');
        }

        // ACTIVAR
        if (e.target.classList.contains('activate')) {
            activateNameSpan.textContent = nombre;
            confirmActivateBtn.dataset.id = id;
            activateModal.classList.add('visible');
        }

        // ELIMINAR
        if (e.target.classList.contains('permanent-delete')) {
            deleteNameSpan.textContent = nombre;
            confirmDeleteBtn.dataset.id = id;
            deleteModal.classList.add('visible');
        }
    });

    // Manejar confirmación de DESACTIVAR
    confirmDeactivateBtn.addEventListener('click', async () => {
        const id = confirmDeactivateBtn.dataset.id;
        const formData = new FormData();
        formData.append('id', id);
        formData.append('estatus', 'Inactivo');
        try {
            const response = await fetch('/sieteveintitres/php/admins/cambiar_estado_membresia.php', { method: 'POST', body: formData });
            const result = await response.json();

            if (result.success) {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'Membresía desactivada',
                    showConfirmButton: false,
                    timer: 2000,
                    ...swalDark
                });
                deactivateModal.classList.remove('visible');
                fetchAndRenderData();
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
            Swal.fire({
                title: 'Error de Conexión',
                text: 'No se pudo desactivar la membresía.',
                icon: 'error',
                confirmButtonText: 'Aceptar',
                ...swalDark
            });
        }
    });

    // Manejar confirmación de ACTIVAR
    confirmActivateBtn.addEventListener('click', async () => {
        const id = confirmActivateBtn.dataset.id;
        const formData = new FormData();
        formData.append('id', id);
        formData.append('estatus', 'Activo');
        try {
            const response = await fetch('/sieteveintitres/php/admins/cambiar_estado_membresia.php', { method: 'POST', body: formData });
            const result = await response.json();

            if (result.success) {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'Membresía activada',
                    showConfirmButton: false,
                    timer: 2000,
                    ...swalDark
                });
                activateModal.classList.remove('visible');
                fetchAndRenderData();
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
            Swal.fire({
                title: 'Error de Conexión',
                text: 'No se pudo activar la membresía.',
                icon: 'error',
                confirmButtonText: 'Aceptar',
                ...swalDark
            });
        }
    });

    confirmDeleteBtn.addEventListener('click', async () => {
        const id = confirmDeleteBtn.dataset.id;
        const formData = new FormData();
        formData.append('id', id);

        try {
            const response = await fetch('/sieteveintitres/php/admins/eliminar_membresia.php', { method: 'POST', body: formData });
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
                deleteModal.classList.remove('visible');
                fetchAndRenderData();
            } else {
                Swal.fire({
                    title: 'Error al Eliminar',
                    text: result.message, // "No se puede eliminar, hay membresías activas."
                    icon: 'error',
                    confirmButtonText: 'Entendido',
                    ...swalDark
                });
            }
        } catch (error) {
            Swal.fire({
                title: 'Error de Conexión',
                text: 'No se pudo eliminar la membresía.',
                icon: 'error',
                confirmButtonText: 'Aceptar',
                ...swalDark
            });
        }
    });

    // Cerrar modales de confirmación
    [deactivateModal, activateModal, deleteModal].forEach(modal => {
        modal.querySelectorAll('.close-btn, .btn-secondary').forEach(btn => {
            btn.addEventListener('click', () => modal.classList.remove('visible'));
        });
    });

    fetchAndRenderData();
});