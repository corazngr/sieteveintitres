document.addEventListener('DOMContentLoaded', function () {
    const submitButton = document.getElementById('send-whatsapp-btn');
    const membershipDetailsContainer = document.getElementById('membership-details'); // Nuevo contenedor
    const staffWhatsappNumber = '5217471491562';

    // Leemos el ID de la membresía desde la URL
    const urlParams = new URLSearchParams(window.location.search);
    const membresiaId = urlParams.get('membresia_id');

    if (!membresiaId) {
        membershipDetailsContainer.innerHTML = '<p class="error">Error: No se ha especificado una membresía.</p>';
        submitButton.disabled = true;
        return;
    }

    // Usamos Promise.all para hacer ambas peticiones al mismo tiempo
    Promise.all([
        fetch('/sieteveintitres/php/auth/verificar_sesion.php'),
        fetch(`/sieteveintitres/php/publico/procesar_pago.php?id=${membresiaId}`)
    ])
        .then(responses => Promise.all(responses.map(res => res.json())))
        .then(([sessionData, membershipData]) => {

            const riderName = sessionData.loggedIn ? sessionData.nombre : '[Escribe tu nombre completo aquí]';

            if (!membershipData.success) {
                throw new Error(membershipData.message);
            }

            const membershipName = membershipData.data.nombre;

            // Mostramos en la página qué membresía se está pagando
            membershipDetailsContainer.innerHTML = `
            <p>Estás a punto de activar la membresía:</p>
            <h3>${membershipName}</h3>
        `;

            // Construimos el mensaje de WhatsApp con los datos obtenidos
            const whatsappMessage = encodeURIComponent(
                `¡Hola, Siete Veintitrés! 👋

                    Quisiera activar mi membresía. A continuación, adjunto mi comprobante.

                    📄 *MIS DATOS:*
                    *Nombre Completo:* ${riderName}
                    *Membresía Pagada:* ${membershipName}
                    *Fecha de Transferencia:* [Escribe la fecha aquí]

                    Quedo al pendiente de la confirmación. ¡Gracias!`
            );

            // Activamos el botón y le asignamos la URL correcta
            submitButton.disabled = false;
            submitButton.addEventListener('click', () => {
                const whatsappUrl = `https://wa.me/${staffWhatsappNumber}?text=${whatsappMessage}`;
                window.open(whatsappUrl, '_blank');
            });

        })
        .catch(error => {
            console.error('Error al preparar los datos de pago:', error);
            membershipDetailsContainer.innerHTML = `<p class="error">Error al cargar los datos. Por favor, intenta de nuevo.</p>`;
            submitButton.disabled = true;
        });
});