document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('contact-form');

    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            const nombre = this.querySelector('input[name="nombre"]').value;
            const correo = this.querySelector('input[name="correo"]').value;
            const telefono = this.querySelector('input[name="telefono"]').value;
            const comoSeEntero = this.querySelector('select[name="como_se_entero"]').value;
            const mensaje = this.querySelector('textarea[name="mensaje"]').value;
            const tuTelefono = '5217471491562';

            const textoMensaje = encodeURIComponent(
                `¡Hola! 😄 Vengo del formulario de contacto de Siete Veintitrés 🚲

                Mi nombre es: ${nombre}
                Correo: ${correo}
                Teléfono: ${telefono}
                Me enteré de 723 por: ${comoSeEntero}

                El mensaje que dejo es el siguiente:
                ${mensaje}`
            );

            const urlWhatsApp = `https://wa.me/${tuTelefono}?text=${textoMensaje}`;
            window.open(urlWhatsApp, '_blank');
            form.reset();
        });
    }
});