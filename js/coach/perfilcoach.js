document.addEventListener('DOMContentLoaded', () => {
    // --- SELECCIÓN DE ELEMENTOS ---
    const coachName = document.getElementById('coach-name');
    const coachEmail = document.getElementById('coach-email');
    const coachPhone = document.getElementById('coach-phone');
    const profileImage = document.getElementById('profile-image');
    const coachStatus = document.getElementById('coach-status');

    // --- Elementos de estadísticas con ID ---
    const statClases = document.getElementById('stat-clases');
    const statRiders = document.getElementById('stat-riders');
    const statCustomValor = document.getElementById('stat-custom-valor');
    const statCustomTitulo = document.getElementById('stat-custom-titulo');
    const editBtn = document.getElementById('edit-btn');
    const saveBtn = document.getElementById('save-btn');
    const fileUpload = document.getElementById('file-upload');
    const uploadIcon = document.getElementById('upload-icon');
    let selectedFile = null;

    // --- FUNCIÓN PARA CARGAR DATOS INICIALES ---
    async function cargarPerfil() {
        try {
            const response = await fetch('/sieteveintitres/php/coaches/perfil_coach.php');
            const result = await response.json();

            if (result.success) {
                const data = result.data;
                coachName.textContent = data.nombre_coach;
                coachEmail.textContent = data.email_coach;
                coachPhone.textContent = data.telefono_coach;

                profileImage.src = data.foto_coach || '/sieteveintitres/images/icono-default.jpg';

                statClases.textContent = data.clases_impartidas;
                statRiders.textContent = data.riders_entrenados;

                if (data.fecha_registro) {
                    const fechaRegistro = new Date(data.fecha_registro);
                    const anio = fechaRegistro.getFullYear();

                    if (anio > 2000) {
                        statCustomTitulo.textContent = "Miembro desde";
                        statCustomValor.textContent = anio;
                    } else {
                        statCustomTitulo.textContent = "Antigüedad";
                        statCustomValor.textContent = "N/A";
                    }
                }

                if (data.estatus) {
                    coachStatus.textContent = data.estatus;
                    coachStatus.className = 'status-' + data.estatus.toLowerCase();
                }
            } else {
                // --- ¡CAMBIO 1: Notificación de error al cargar! ---
                Swal.fire({
                    title: 'Error al Cargar Perfil',
                    text: result.message,
                    icon: 'error',
                    confirmButtonText: 'Aceptar',
                    background: '#1a1a1a',
                    color: '#fff'
                });
            }
        } catch (error) {
            console.error('Error de conexión:', error);
            // --- ¡CAMBIO 2: Notificación de error de conexión! ---
            Swal.fire({
                title: 'Error de Conexión',
                text: 'No se pudieron cargar los datos del perfil.',
                icon: 'error',
                confirmButtonText: 'Aceptar',
                background: '#1a1a1a',
                color: '#fff'
            });
        }
    }

    // Cargar los datos del perfil al entrar a la página
    cargarPerfil();

    // Ocultar botón de guardar e icono de carga inicialmente
    saveBtn.style.display = 'none';
    uploadIcon.style.display = 'none';

    // --- MODO EDICIÓN ---
    editBtn.addEventListener('click', (e) => {
        e.preventDefault();
        const currentName = coachName.textContent;
        const currentEmail = coachEmail.textContent;
        const currentPhone = coachPhone.textContent;

        coachName.innerHTML = `<input type="text" value="${currentName}" class="editable-input" id="input-name">`;
        coachEmail.innerHTML = `<input type="email" value="${currentEmail}" class="editable-input" id="input-email">`;
        coachPhone.innerHTML = `<input type="tel" value="${currentPhone}" class="editable-input" id="input-phone">`;

        editBtn.style.display = 'none';
        saveBtn.style.display = 'inline-flex';
        uploadIcon.style.display = 'flex';
    });

    // --- PREVISUALIZACIÓN DE IMAGEN ---
    fileUpload.addEventListener('change', (event) => {
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = (e) => { profileImage.src = e.target.result; };
            reader.readAsDataURL(file);
            selectedFile = file;
        } else {
            selectedFile = null;
        }
    });

    // --- GUARDAR CAMBIOS ---
    saveBtn.addEventListener('click', async (e) => {
        e.preventDefault();

        const newName = document.getElementById('input-name').value;
        const newEmail = document.getElementById('input-email').value;
        const newPhone = document.getElementById('input-phone').value;

        // Usamos FormData para enviar texto y archivos juntos
        const formData = new FormData();
        formData.append('nombre', newName);
        formData.append('email', newEmail);
        formData.append('telefono', newPhone);

        if (selectedFile) {
            formData.append('foto_coach', selectedFile);
        }

        try {
            // Nota: Al usar FormData, no establezcas el header 'Content-Type'. El navegador lo hace por ti.
            const response = await fetch('/sieteveintitres/php/coaches/actualizar_perfil_coach.php', {
                method: 'POST',
                body: formData,
            });
            const result = await response.json();

            if (result.success) {
                // Volver a modo visualización
                coachName.textContent = newName;
                coachEmail.textContent = newEmail;
                coachPhone.textContent = newPhone;
                // Si el servidor devolvió una nueva URL de imagen, la actualizamos
                if (result.newImageUrl) {
                    profileImage.src = result.newImageUrl;
                }

                saveBtn.style.display = 'none';
                editBtn.style.display = 'inline-flex';
                uploadIcon.style.display = 'none';
                selectedFile = null;
                Swal.fire({
                    title: '¡Guardado!',
                    text: 'Tu perfil se ha actualizado con éxito.',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false,
                    background: '#1a1a1a',
                    color: '#fff'
                });
            } else {
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
            console.error('Error al guardar el perfil:', error);
            Swal.fire({
                title: 'Error de Conexión',
                text: 'Hubo un error de conexión al guardar los cambios.',
                icon: 'error',
                confirmButtonText: 'Aceptar',
                background: '#1a1a1a',
                color: '#fff'
            });
        }
    });
});