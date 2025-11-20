document.addEventListener('DOMContentLoaded', function () {
    const userTypeRadios = document.querySelectorAll('input[name="userType"]');
    const dynamicFormFields = document.getElementById('dynamicFormFields');

    function updateFormFields(userType) {
        let fieldsHTML = '';
        if (userType === 'rider') {
            fieldsHTML = `<div class="form-group"><label for="photoInput">Foto de Perfil</label><input type="file" id="photoInput" name="photo" accept="image/*"></div>`;
        } else if (userType === 'coach') {
            fieldsHTML = `<div class="form-group"><label for="rfcInput">RFC</label><input type="text" id="rfcInput" name="rfcInput" placeholder="Tu RFC" required maxlength="13"></div><br><div class="form-group"><label for="photoInput">Foto de Perfil</label><input type="file" id="photoInput" name="photo" accept="image/*"></div>`;
        }
        dynamicFormFields.innerHTML = fieldsHTML;
    }
    userTypeRadios.forEach(radio => radio.addEventListener('change', (event) => updateFormFields(event.target.value)));
    updateFormFields(document.querySelector('input[name="userType"]:checked').value);

    function showValidationError(title, text) {
        Swal.fire({
            title: title,
            text: text,
            icon: 'error',
            confirmButtonText: 'Entendido',
            background: '#1a1a1a',
            color: '#fff'
        });
    }

    // --- Función para validar la lógica del formulario ---
    function validateForm(formData) {
        const name = formData.get('nameInput');
        const email = formData.get('emailInput');
        const phone = formData.get('phoneInput');

        // 1. Validar Nombre (mín. 3 letras, solo letras y espacios, acepta acentos)
        if (!/^[a-zA-ZÀ-ÿ\s]{3,}$/.test(name)) {
            showValidationError('Nombre Inválido', 'Por favor, ingresa un nombre válido (solo letras y espacios, mín. 3 caracteres).');
            return false;
        }

        // 2. Validar Email
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            showValidationError('Email Inválido', 'Por favor, ingresa un correo electrónico válido.');
            return false;
        }

        // 3. Validar Teléfono (exactamente 10 dígitos numéricos)
        if (!/^\d{10}$/.test(phone)) {
            showValidationError('Teléfono Inválido', 'Tu número de teléfono debe contener 10 dígitos (solo números).');
            return false;
        }

        return true;
    }


    // --- MANEJAR EL ENVÍO DEL FORMULARIO ---
    const registerForm = document.getElementById('registerForm');
    registerForm.addEventListener('submit', function (event) {
        event.preventDefault();

        const formData = new FormData(registerForm);

        if (!validateForm(formData)) {
            return;
        }

        const submitButton = registerForm.querySelector('button[type="submit"]');
        submitButton.textContent = 'Registrando...';
        submitButton.disabled = true;

        fetch('/sieteveintitres/php/publico/registro.php', {
            method: 'POST',
            body: formData
        })
            .then(response => {
                if (!response.ok) throw new Error('Error del servidor.');
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    registerForm.reset();
                    Swal.fire({
                        title: '¡Registro Exitoso!',
                        text: data.message,
                        icon: 'success',
                        confirmButtonText: 'Iniciar Sesión',
                        background: '#1a1a1a',
                        color: '#fff'
                    }).then(() => {
                        window.location.href = '/sieteveintitres/html/publico/iniciosesion.html';
                    });
                } else {
                    Swal.fire({
                        title: 'Error en el Registro',
                        text: data.message,
                        icon: 'error',
                        confirmButtonText: 'Entendido',
                        background: '#1a1a1a',
                        color: '#fff'
                    });
                }
            })
            .catch(error => {
                console.error('Error en la solicitud:', error);
                Swal.fire({
                    title: 'Error de Conexión',
                    text: 'Ocurrió un error de comunicación. Inténtalo de nuevo.',
                    icon: 'error',
                    confirmButtonText: 'Aceptar',
                    background: '#1a1a1a',
                    color: '#fff'
                });
            })
            .finally(() => {
                submitButton.textContent = 'Registrarse';
                submitButton.disabled = false;
            });
    });
});