document.addEventListener('DOMContentLoaded', function () {

    // --- 1. CÓDIGO PARA CAMBIAR LA APARIENCIA DEL FORMULARIO ---
    const userTypeRadios = document.querySelectorAll('input[name="userType"]');
    const credentialLabel = document.getElementById('credentialLabel');
    const credentialInput = document.getElementById('credentialInput');
    const emailLabel = document.querySelector('label[for="emailInput"]');
    const emailInput = document.getElementById('emailInput');
    const togglePassword = document.getElementById('togglePassword');
    const forgotLink = document.getElementById('forgotLink');

    function updateFormFields(userType) {
        switch (userType) {
            case 'rider':
                emailLabel.textContent = 'Correo Electrónico:';
                emailInput.placeholder = 'tu.correo@gmail.com';
                credentialLabel.textContent = 'Código de Acceso:';
                credentialInput.type = 'password';
                credentialInput.placeholder = '****';
                credentialInput.maxLength = 4;
                credentialInput.pattern = '[A-Za-z0-9]{4}';
                togglePassword.style.display = 'block';
                forgotLink.style.display = 'block';
                forgotLink.textContent = '¿Olvidaste tu PIN de acceso?';
                break;
            case 'coach':
                emailLabel.textContent = 'Correo Electrónico:';
                emailInput.placeholder = 'coach@siete23.com';
                credentialLabel.textContent = 'RFC:';
                credentialInput.type = 'text';
                credentialInput.placeholder = 'ABCD123456XYZ';
                credentialInput.maxLength = 13;
                credentialInput.removeAttribute('pattern');
                togglePassword.style.display = 'none';
                forgotLink.style.display = 'none';
                break;
            case 'admin':
                emailLabel.textContent = 'Correo Electrónico:';
                emailInput.placeholder = 'admin@siete23.com';
                credentialLabel.textContent = 'Contraseña:';
                credentialInput.type = 'password';
                credentialInput.placeholder = '••••••••';
                credentialInput.removeAttribute('maxLength');
                credentialInput.removeAttribute('pattern');
                togglePassword.style.display = 'block';
                forgotLink.style.display = 'none';
                break;
        }
        togglePassword.classList.remove('fa-eye-slash');
        togglePassword.classList.add('fa-eye');
    }

    userTypeRadios.forEach(radio => {
        radio.addEventListener('change', function () {
            emailInput.value = '';
            credentialInput.value = '';
            updateFormFields(this.value);
        });
    });

    updateFormFields('rider');

    togglePassword.addEventListener('click', function () {
        const type = credentialInput.getAttribute('type') === 'password' ? 'text' : 'password';
        credentialInput.setAttribute('type', type);

        this.classList.toggle('fa-eye');
        this.classList.toggle('fa-eye-slash');
    });

    const loginForm = document.getElementById('loginForm');

    loginForm.addEventListener('submit', function (event) {
        event.preventDefault();
        const formData = new FormData(loginForm);

        fetch('/sieteveintitres/php/publico/iniciosesion.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // --- ¡CAMBIO 1: Notificación de Éxito y Redirección! ---
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: '¡Bienvenido! Redirigiendo...',
                        showConfirmButton: false,
                        timer: 1500, // 1.5 segundos
                        background: '#1a1a1a',
                        color: '#fff'
                    }).then(() => {
                        window.location.href = data.redirectUrl; // Redirigir después
                    });
                } else {
                    // --- ¡CAMBIO 2: Reemplazo de errorBox por Notificación de Error! ---
                    Swal.fire({
                        title: 'Error al Iniciar Sesión',
                        text: data.message || 'Error desconocido.',
                        icon: 'error',
                        confirmButtonText: 'Entendido',
                        background: '#1a1a1a',
                        color: '#fff'
                    });
                }
            })
            .catch(error => {
                console.error('Error en la solicitud fetch:', error);
                // --- ¡CAMBIO 3: Notificación de Error de Conexión! ---
                Swal.fire({
                    title: 'Error de Conexión',
                    text: 'Ocurrió un error de comunicación. Revisa tu conexión a internet.',
                    icon: 'error',
                    confirmButtonText: 'Aceptar',
                    background: '#1a1a1a',
                    color: '#fff'
                });
            });
    });


    // --- 3. CÓDIGO PARA MANEJAR EL FORMULARIO DEL MODAL ---
    const forgotPasswordForm = document.getElementById('forgotPasswordForm');

    forgotPasswordForm.addEventListener('submit', function (event) {
        event.preventDefault();
        const formData = new FormData(forgotPasswordForm);
        const submitButton = forgotPasswordForm.querySelector('button[type="submit"]');
        submitButton.textContent = 'Enviando...';
        submitButton.disabled = true;

        fetch('/sieteveintitres/php/publico/recuperarcontra.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // --- ¡CAMBIO 4: Notificación de Éxito del Modal! ---
                    Swal.fire({
                        title: '¡Solicitud Enviada!',
                        text: data.message, // "Si el correo existe, te enviaremos tu PIN."
                        icon: 'success',
                        confirmButtonText: 'Genial',
                        background: '#1a1a1a',
                        color: '#fff'
                    }).then(() => {
                        closeModal(); // Cerrar el modal después de la notificación
                    });
                } else {
                    // --- ¡CAMBIO 5: Notificación de Error del Modal! ---
                    Swal.fire({
                        title: 'Error',
                        text: data.message,
                        icon: 'error',
                        confirmButtonText: 'Entendido',
                        background: '#1a1a1a',
                        color: '#fff'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                // --- ¡CAMBIO 6: Notificación de Error de Conexión del Modal! ---
                Swal.fire({
                    title: 'Error de Conexión',
                    text: 'No se pudo procesar la solicitud. Inténtalo de nuevo.',
                    icon: 'error',
                    confirmButtonText: 'Aceptar',
                    background: '#1a1a1a',
                    color: '#fff'
                });
            })
            .finally(() => {
                submitButton.textContent = 'Enviar';
                submitButton.disabled = false;
            });
    });
});

// --- FUNCIONES PARA EL MODAL ---
function openModal() {
    document.getElementById("forgotModal").style.display = "flex";
}

function closeModal() {
    document.getElementById("forgotModal").style.display = "none";
}

window.onclick = function (event) {
    let modal = document.getElementById("forgotModal");
    if (event.target == modal) {
        modal.style.display = "none";
    }
}