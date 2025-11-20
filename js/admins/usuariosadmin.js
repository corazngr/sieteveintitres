document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('searchInput');
    const roleFilter = document.getElementById('roleFilter');
    const statusFilter = document.getElementById('statusFilter');
    const userTableBody = document.getElementById('userTableBody');
    const assignModal = document.getElementById('assignMembershipModal');
    const membershipTypeSelect = document.getElementById('membershipTypeSelect');
    let availableMemberships = [];

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

    // Función para obtener y mostrar los usuarios
    async function fetchUsers() {
        userTableBody.innerHTML = '<tr><td colspan="5" class="text-center">Cargando usuarios...</td></tr>';
        const searchTerm = searchInput.value;
        const selectedRole = roleFilter.value;
        const selectedStatus = statusFilter.value;
        const url = `/sieteveintitres/php/admins/gestionar_usuarios.php?search=${searchTerm}&role=${selectedRole}&status=${selectedStatus}`;

        try {
            const response = await fetch(url);
            const result = await response.json();
            userTableBody.innerHTML = '';

            if (result.success) {
                availableMemberships = result.tipos_membresia || [];

                if (result.data.length > 0) {
                    result.data.forEach(user => {

                        const esActivo = user.esta_activo == 1;
                        const statusClass = esActivo ? 'status-active' : 'status-inactive';
                        const statusText = esActivo ? 'Activo' : 'Inactivo';

                        const defaultAvatar = '/sieteveintitres/images/icono-default.jpg';

                        let fotoSrc = defaultAvatar;
                        if (user.foto) {
                            if (user.tipo_usuario === 'rider') {
                                fotoSrc = `/sieteveintitres/uploads/riders/${user.foto}`;
                            } else if (user.tipo_usuario === 'coach') {
                                fotoSrc = user.foto;
                            }
                        }

                        let actionButtonsHTML = '';
                        if (user.tipo_usuario === 'rider') {
                            if (esActivo) {
                                // Rider Activo: Mostrar "Desactivar"
                                actionButtonsHTML = `
                                    <button class="action-btn delete" data-id="${user.id_usuario}" data-specific-id="${user.specific_id}" data-type="rider" data-name="${user.nombre}" title="Desactivar Usuario">
                                        <i class="fa-solid fa-user-slash"></i>
                                    </button>`;
                            }
                            // Rider Inactivo: No muestra nada (se activa con membresía)

                        } else if (user.tipo_usuario === 'coach') {
                            if (esActivo) {
                                // Coach Activo: Mostrar "Desactivar"
                                actionButtonsHTML = `
                                    <button class="action-btn delete" data-id="${user.id_usuario}" data-specific-id="${user.specific_id}" data-type="coach" data-name="${user.nombre}" title="Desactivar Usuario">
                                        <i class="fa-solid fa-user-slash"></i>
                                    </button>`;
                            } else {
                                // Coach Inactivo: Mostrar "Reactivar"
                                actionButtonsHTML = `
                                    <button class="action-btn reactivate" data-id="${user.id_usuario}" data-specific-id="${user.specific_id}" data-type="coach" data-name="${user.nombre}" title="Reactivar Usuario">
                                        <i class="fa-solid fa-user-check"></i>
                                    </button>`;
                            }
                        }
                        // Admin: No muestra botones de acción

                        const membershipButtonHTML = user.tipo_usuario === 'rider' ? `
                            <button class="action-btn assign-membership" data-rider-id="${user.specific_id}" data-name="${user.nombre}" title="Asignar Membresía">
                                <i class="fa-solid fa-id-card"></i>
                            </button>
                        ` : '';

                        const rowHTML = `
                        <tr>
                            <td>
                                <div class="user-profile">
                                    <img src="${fotoSrc}" alt="Avatar" class="user-avatar" onerror="this.src='${defaultAvatar}'">
                                    <div class="user-info">
                                        <span class="user-name">${user.nombre}</span>
                                        <span class="user-email">${user.email}</span>
                                    </div>
                                </div>
                            </td>
                            <td><span class="user-phone">${user.telefono || 'N/A'}</span></td>
                            <td>${user.tipo_usuario.charAt(0).toUpperCase() + user.tipo_usuario.slice(1)}</td>
                            <td><span class="status-badge ${statusClass}">${statusText}</span></td>
                            <td>
                                ${membershipButtonHTML}
                                ${actionButtonsHTML}
                            </td>
                        </tr>
                        `;
                        userTableBody.innerHTML += rowHTML;
                    });
                } else {
                    userTableBody.innerHTML = '<tr><td colspan="5" class="text-center">No se encontraron usuarios con los filtros aplicados.</td></tr>';
                }
            } else {
                userTableBody.innerHTML = `<tr><td colspan="5" class="text-center">${result.message}</td></tr>`;
                Swal.fire({
                    title: 'Error',
                    text: result.message,
                    icon: 'error',
                    confirmButtonText: 'Aceptar',
                    ...swalDark
                });
            }
        } catch (error) {
            console.error('Error:', error);
            userTableBody.innerHTML = '<tr><td colspan="5" class="text-center">Ocurrió un error al cargar los datos. Revisa la consola.</td></tr>';
            Swal.fire({
                title: 'Error de Conexión',
                text: 'No se pudieron cargar los usuarios. Revisa tu conexión.',
                icon: 'error',
                confirmButtonText: 'Aceptar',
                ...swalDark
            });
        }
    }

    // Event listeners para los filtros
    searchInput.addEventListener('input', fetchUsers);
    roleFilter.addEventListener('change', fetchUsers);
    statusFilter.addEventListener('change', fetchUsers);

    userTableBody.addEventListener('click', async function (e) {
        const deleteButton = e.target.closest('.delete');
        if (deleteButton) {
            const userName = deleteButton.dataset.name;
            const id = deleteButton.dataset.id;
            const specificId = deleteButton.dataset.specificId;
            const type = deleteButton.dataset.type;

            const confirmation = await Swal.fire({
                title: `¿Desactivar a ${userName}?`,
                text: "El historial del usuario se mantendrá, pero no podrá iniciar sesión.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, desactivar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#d33',
                ...swalDark
            });

            if (confirmation.isConfirmed) {
                const formData = new FormData();
                formData.append('id', id);
                formData.append('specific_id', specificId);
                formData.append('tipo', type);

                fetch('/sieteveintitres/php/admins/eliminar_usuario.php', {
                    method: 'POST',
                    body: formData
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                toast: true,
                                position: 'top-end',
                                icon: 'success',
                                title: data.message,
                                showConfirmButton: false,
                                timer: 2000,
                                ...swalDark
                            });
                            fetchUsers();
                        } else {
                            Swal.fire({
                                title: 'Error',
                                text: data.message,
                                icon: 'error',
                                confirmButtonText: 'Entendido',
                                ...swalDark
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            title: 'Error de Conexión',
                            text: 'No se pudo desactivar el usuario.',
                            icon: 'error',
                            confirmButtonText: 'Aceptar',
                            ...swalDark
                        });
                    });
            }
        }

        const reactivateButton = e.target.closest('.reactivate');
        if (reactivateButton) {
            const userName = reactivateButton.dataset.name;
            const specificId = reactivateButton.dataset.specificId;
            const type = reactivateButton.dataset.type;

            const confirmation = await Swal.fire({
                title: `¿Reactivar a ${userName}?`,
                text: "El coach podrá volver a iniciar sesión.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, reactivar',
                cancelButtonText: 'Cancelar',
                ...swalDark
            });

            if (confirmation.isConfirmed) {
                const formData = new FormData();
                formData.append('specific_id', specificId);
                formData.append('tipo', type);

                fetch('/sieteveintitres/php/admins/reactivar_usuario.php', {
                    method: 'POST',
                    body: formData
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                toast: true,
                                position: 'top-end',
                                icon: 'success',
                                title: data.message,
                                showConfirmButton: false,
                                timer: 2000,
                                ...swalDark
                            });
                            fetchUsers();
                        } else {
                            Swal.fire({
                                title: 'Error',
                                text: data.message,
                                icon: 'error',
                                confirmButtonText: 'Entendido',
                                ...swalDark
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            title: 'Error de Conexión',
                            text: 'No se pudo reactivar el usuario.',
                            icon: 'error',
                            confirmButtonText: 'Aceptar',
                            ...swalDark
                        });
                    });
            }
        }

        const assignBtn = e.target.closest('.assign-membership');
        if (assignBtn) {
            const riderId = assignBtn.dataset.riderId;
            const riderName = assignBtn.dataset.name;
            document.getElementById('assignRiderName').textContent = riderName;
            document.getElementById('assignRiderId').value = riderId;
            membershipTypeSelect.innerHTML = '<option value="" disabled selected>Selecciona un tipo</option>';
            availableMemberships.forEach(tipo => {
                membershipTypeSelect.innerHTML += `<option value="${tipo.id_tipo_membresia}">${tipo.nombre} ($${tipo.precio})</option>`;
            });
            document.getElementById('membershipStartDate').valueAsDate = new Date();
            assignModal.classList.add('visible');
        }
    });

    assignModal.querySelectorAll('.close-btn, [data-close-modal]').forEach(btn => {
        btn.addEventListener('click', () => assignModal.classList.remove('visible'));
    });
    document.getElementById('saveAssignedMembershipBtn').addEventListener('click', async () => {
        const formData = new FormData(document.getElementById('assignMembershipForm'));
        try {
            const response = await fetch('/sieteveintitres/php/admins/asignar_membresia.php', {
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
                assignModal.classList.remove('visible');
                fetchUsers();
            } else {
                Swal.fire({
                    title: 'Error al Asignar',
                    text: result.message,
                    icon: 'error',
                    confirmButtonText: 'Entendido',
                    ...swalDark
                });
            }
        } catch (error) {
            Swal.fire({
                title: 'Error de Conexión',
                text: 'No se pudo asignar la membresía.',
                icon: 'error',
                confirmButtonText: 'Aceptar',
                ...swalDark
            });
        }
    });

    fetchUsers();
});