document.addEventListener('DOMContentLoaded', () => {
    // --- PESTAÑAS ---
    const tabLinks = document.querySelectorAll('.tab-link');
    const tabPanels = document.querySelectorAll('.tab-panel');

    tabLinks.forEach(link => {
        link.addEventListener('click', () => {
            const target = link.dataset.target;
            tabLinks.forEach(l => l.classList.remove('active'));
            tabPanels.forEach(p => p.classList.remove('active'));
            link.classList.add('active');
            document.getElementById(target).classList.add('active');
        });
    });

    // --- ELEMENTOS DEL PERFIL ---
    const profileInputs = {
        nombre: document.getElementById('nombre-input'),
        correo: document.getElementById('correo-input'),
        telefono: document.getElementById('telefono-input'),
        foto: document.getElementById('foto-input')
    };
    const profileViews = {
        nombre: document.getElementById('nombre-view'),
        correo: document.getElementById('correo-view'),
        telefono: document.getElementById('telefono-view'),
        profileImg: document.getElementById('profile-img')
    };

    // --- ELEMENTOS DE MEMBRESÍA ---
    const membershipViews = {
        tipo: document.getElementById('membresia-view'),
        inicio: document.getElementById('fecha-inicio-view'),
        vence: document.getElementById('fecha-vence-view')
    };

    // --- CARGAR DATOS DEL USUARIO ---
    // Dentro de tu archivo miusuario.js

    async function cargarDatosUsuario() {
        try {
            const res = await fetch(`/sieteveintitres/php/publico/obtener_datos.php?v=${new Date().getTime()}`);
            const result = await res.json();

            if (!result.success) {
                Swal.fire({
                    title: 'Error al Cargar Datos',
                    text: result.message,
                    icon: 'error',
                    confirmButtonText: 'Aceptar',
                    background: '#1a1a1a', // Tema oscuro
                    color: '#fff'         // Texto blanco
                }).then(() => {
                    if (result.redirectUrl) {
                        window.location.href = result.redirectUrl;
                    }
                });
                return;
            }

            const data = result.data;

            // --- Pestaña: PERFIL ---
            profileInputs.nombre.value = data.nombre_rider;
            profileViews.nombre.textContent = data.nombre_rider;

            // AÑADE ESTAS LÍNEAS
            profileInputs.correo.value = data.email_rider;
            profileViews.correo.textContent = data.email_rider;

            profileInputs.telefono.value = data.telefono_rider;
            profileViews.telefono.textContent = data.telefono_rider;

            profileViews.profileImg.src = data.foto_rider
                ? `/sieteveintitres/uploads/riders/${data.foto_rider}?v=${new Date().getTime()}` // <-- Añadimos el prefijo
                : '/sieteveintitres/images/rosa723.jpg';

            // --- Pestaña: MEMBRESÍA (NUEVO) ---
            const membresia = data.membresia; // puede ser null
            if (membresia) {
                document.getElementById('membresia-view').textContent = membresia.tipo_membresia;
                document.getElementById('fecha-inicio-view').textContent = new Date(membresia.fecha_inicio + 'T00:00:00').toLocaleDateString('es-MX');
                document.getElementById('fecha-vence-view').textContent = new Date(membresia.fecha_fin + 'T00:00:00').toLocaleDateString('es-MX');
            } else {
                document.getElementById('membresia-view').textContent = "Sin membresía activa";
                document.getElementById('fecha-inicio-view').textContent = "-";
                document.getElementById('fecha-vence-view').textContent = "-";
            }
            document.getElementById('codigo-view').textContent = data.codigo_acceso || 'N/A';

            // --- Pestaña: HISTORIAL ---
            const historial = data.historial;
            const historialTbody = document.querySelector('.reservations-table tbody');
            historialTbody.innerHTML = ''; // Limpiamos la tabla

            if (historial && historial.length > 0) {
                historial.forEach(reserva => {
                    const fechaClase = new Date(`${reserva.fecha}T${reserva.hora_inicio}`);
                    const ahora = new Date();

                    // Comprobamos si la clase ya pasó (considerando la hora actual)
                    const isPast = fechaClase < ahora;

                    // Formateamos los datos para mostrarlos
                    const fechaFormateada = fechaClase.toLocaleDateString('es-ES', { weekday: 'long', day: 'numeric', month: 'long' });
                    const horaFormateada = fechaClase.toLocaleTimeString('es-ES', { hour: 'numeric', minute: '2-digit', hour12: true });

                    // Creamos el botón correspondiente
                    let buttonHTML = '';

                    if (isPast) {
                        // Para clases pasadas, sigue mostrando "Clase Finalizada"
                        buttonHTML = '<button class="btn-finalizada" disabled>Clase Finalizada</button>';
                    } else {
                        // Para clases futuras, muestra "Pendiente" con la nueva clase CSS
                        buttonHTML = '<button class="btn-pendiente" disabled>Pendiente</button>';
                    }

                    const fila = `
                        <tr data-fecha="${fechaFormateada}" data-hora="${horaFormateada}" data-coach="${reserva.nombre_coach}">
                            <td>
                                <div class="date-time-cell">
                                    <span class="cell-date">${fechaFormateada}</span>
                                    <span class="cell-time">${horaFormateada}</span>
                                </div>
                            </td>
                            <td>${reserva.nombre_coach}</td>
                            <td>Bici #${reserva.id_bicicleta}</td>
                            <td>${buttonHTML}</td>
                        </tr>
                    `;
                    historialTbody.insertAdjacentHTML('beforeend', fila);
                });
            } else {
                const filaVacia = `<tr><td colspan="4">No tienes reservaciones en tu historial.</td></tr>`;
                historialTbody.innerHTML = filaVacia;
            }

        } catch (err) {
            console.error(err);
            Swal.fire({
                title: 'Error de Conexión',
                text: 'No se pudieron cargar los datos del perfil. Revisa tu conexión a internet.',
                icon: 'error',
                confirmButtonText: 'Aceptar',
                background: '#1a1a1a',
                color: '#fff'
            });
        }
    }

    cargarDatosUsuario();

    // --- VISTA PREVIA DE FOTO ---
    profileInputs.foto.addEventListener('change', () => {
        const file = profileInputs.foto.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = e => profileViews.profileImg.src = e.target.result;
            reader.readAsDataURL(file);
        }
    });

    // --- GUARDAR PERFIL ---
    async function saveProfileForm() {
        const form = document.getElementById('profile-form');
        const btn = form.querySelector('button[type="submit"]');
        const formData = new FormData();

        formData.append('nombre', profileInputs.nombre.value);
        formData.append('email', profileInputs.correo.value);
        formData.append('telefono', profileInputs.telefono.value);
        if (profileInputs.foto.files[0]) formData.append('foto', profileInputs.foto.files[0]);

        try {
            btn.disabled = true;
            btn.textContent = "Guardando...";
            const res = await fetch('/sieteveintitres/php/publico/actualizar_datos.php', { method: 'POST', body: formData });
            const result = await res.json();

            btn.disabled = false;
            btn.textContent = "Guardar";

            if (result.success) {
                await cargarDatosUsuario();
                // --- ¡CAMBIO 3: Notificación de ÉXITO al guardar! ---
                Swal.fire({
                    title: '¡Guardado!',
                    text: 'Tu perfil se ha actualizado con éxito.',
                    icon: 'success',
                    timer: 2000, // Se cierra solo después de 2 segundos
                    showConfirmButton: false,
                    background: '#1a1a1a',
                    color: '#fff'
                });
                form.classList.remove('edit-mode');
            } else {
                // --- ¡CAMBIO 4: Notificación de ERROR al guardar! ---
                Swal.fire({
                    title: 'Error al Guardar',
                    text: '❌ ' + result.message,
                    icon: 'error',
                    confirmButtonText: 'Entendido',
                    background: '#1a1a1a',
                    color: '#fff'
                });
            }

        } catch (err) {
            console.error(err);
            // --- ¡CAMBIO 5: Notificación de ERROR DE CONEXIÓN al guardar! ---
            Swal.fire({
                title: 'Error de Conexión',
                text: 'No se pudo guardar el perfil. Revisa tu conexión a internet.',
                icon: 'error',
                confirmButtonText: 'Aceptar',
                background: '#1a1a1a',
                color: '#fff'
            });
            btn.disabled = false;
            btn.textContent = "Guardar";
        }
    }

    // --- FORMULARIOS EDITABLES ---
    const editableForms = document.querySelectorAll('#profile-form');
    editableForms.forEach(form => {
        const editBtn = form.querySelector('.edit-btn');
        const cancelBtn = form.querySelector('.cancel-btn');

        editBtn.addEventListener('click', () => form.classList.add('edit-mode'));
        cancelBtn.addEventListener('click', () => {
            form.classList.remove('edit-mode');
            clearErrors(form);
            cargarDatosUsuario();
        });

        form.addEventListener('submit', e => {
            e.preventDefault();
            if (form.id === 'profile-form' && validateProfileForm()) saveProfileForm();
        });
    });

    // --- VALIDACIONES ---
    function validateProfileForm() {
        clearErrors(document.getElementById('profile-form'));
        let valid = true;
        if (!/^[a-zA-Z\s]+$/.test(profileInputs.nombre.value)) { showError('nombre', 'Solo letras y espacios.'); valid = false; }
        if (!/^\S+@\S+\.\S+$/.test(profileInputs.correo.value)) { showError('correo', 'Correo inválido.'); valid = false; }
        if (!/^\d{10}$/.test(profileInputs.telefono.value)) { showError('telefono', 'Debe tener 10 dígitos.'); valid = false; }
        if (profileInputs.foto.files.length > 0 && profileInputs.foto.files[0].size > 2 * 1024 * 1024) {
            showError('foto', 'Imagen muy grande (máx 2MB).'); valid = false;
        }
        return valid;
    }


    // --- ERRORES ---
    function showError(field, msg) {
        const el = document.getElementById(`${field}-error`);
        const input = document.getElementById(`${field}-input`);
        if (el) el.textContent = msg;
        if (input) input.classList.add('invalid');
    }

    function clearErrors(form) {
        form.querySelectorAll('.error-message').forEach(e => e.textContent = '');
        form.querySelectorAll('input.invalid').forEach(i => i.classList.remove('invalid'));
    }

    // --- BOTÓN COPIAR CÓDIGO ---
    const botonCopiar = document.querySelector('.copy-btn');
    const textoACopiar = document.getElementById('codigo-view');
    if (botonCopiar && textoACopiar) {

        botonCopiar.addEventListener('click', () => {

            const texto = textoACopiar.innerText;

            if (!texto || texto === 'N/A') {
                Swal.fire({
                    text: 'No hay código para copiar.',
                    icon: 'warning',
                    timer: 1500,
                    showConfirmButton: false,
                    background: '#1a1a1a',
                    color: '#fff'
                });
                return;
            }

            navigator.clipboard.writeText(texto).then(() => {
                Swal.fire({
                    toast: true,
                    position: 'top-end', // Arriba a la derecha
                    icon: 'success',
                    title: `¡Código '${texto}' copiado!`,
                    showConfirmButton: false,
                    timer: 2000,
                    timerProgressBar: true,
                    background: '#1a1a1a',
                    color: '#fff'
                });

                const icono = botonCopiar.querySelector('i');
                if (icono) {
                    icono.classList.remove('fa-copy');
                    icono.classList.add('fa-check');
                }

                setTimeout(() => {
                    if (icono) {
                        icono.classList.remove('fa-check');
                        icono.classList.add('fa-copy');
                    }
                }, 2000);

            }).catch(err => {
                console.error('Error al intentar copiar al portapapeles: ', err);
                Swal.fire({
                    title: 'Error al Copiar',
                    text: 'Tu navegador no permitió copiar el código automáticamente.',
                    icon: 'error',
                    confirmButtonText: 'Aceptar',
                    background: '#1a1a1a',
                    color: '#fff'
                });
            });
        });
    }

});
