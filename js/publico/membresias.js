document.addEventListener('DOMContentLoaded', () => {
    const wrapper = document.querySelector('.membership-cards-wrapper');

    // --- LÓGICA DE VALIDACIÓN Y REDIRECCIÓN ---
    wrapper.addEventListener('click', function (event) {
        if (event.target.classList.contains('select-plan-btn')) {
            const card = event.target.closest('.membership-card');
            const membershipId = card.dataset.id;

            // 1. Verificamos la sesión del usuario
            fetch('/sieteveintitres/php/auth/verificar_sesion.php')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Error de red al verificar sesión');
                    }
                    return response.json();
                })
                .then(session => {
                    if (session.loggedIn) {
                        // Usuario conectado: Lo mandamos a la página de pago
                        window.location.href = `/sieteveintitres/html/publico/pago.html?membresia_id=${membershipId}`;
                    } else {
                        // --- ¡CAMBIO AQUÍ! ---
                        // Usuario desconectado: Le pedimos iniciar sesión
                        Swal.fire({
                            title: 'Inicia Sesión para Continuar',
                            text: 'Por favor, inicia sesión o regístrate para comprar una membresía.',
                            icon: 'info',
                            confirmButtonText: 'Iniciar Sesión',
                            background: '#1a1a1a', // Tema oscuro
                            color: '#fff'         // Texto blanco
                        }).then((result) => {
                            // Lo redirigimos SÓLO DESPUÉS de que cierre el modal
                            if (result.isConfirmed) {
                                window.location.href = '/sieteveintitres/html/publico/iniciosesion.html';
                            }
                        });
                    }
                })
                .catch(error => { // --- ¡CAMBIO AQUÍ! (Añadimos un catch) ---
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

    // --- CÓDIGO PARA CARGAR LAS TARJETAS ---
    fetch('/sieteveintitres/php/publico/membresias.php')
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success' && data.data.length > 0) {
                wrapper.innerHTML = '';

                data.data.forEach(membresia => {
                    const card = document.createElement('div');

                    const cardClass = membresia.es_popular == 1 ? 'membership-card featured' : 'membership-card';
                    const badge = membresia.es_popular == 1 ? '<span class="badge">Más Popular</span>' : '';

                    let caracteristicasHtml = '';
                    if (membresia.caracteristicas) {
                        membresia.caracteristicas.split('\n').forEach(feature => {
                            feature = feature.trim();
                            if (feature) {
                                const isEnabled = feature.startsWith('+');
                                const icon = isEnabled ? 'fa-circle-check' : 'fa-circle-xmark';
                                const className = isEnabled ? '' : 'disabled';
                                const text = feature.substring(1).trim();
                                caracteristicasHtml += `<li class="${className}"><i class="fa-solid ${icon}"></i> ${text}</li>`;
                            }
                        });
                    }

                    // 💡 CAMBIO CLAVE: Añadimos 'data-id' a la tarjeta
                    // y usamos una clase en el botón en lugar de 'onclick'.
                    card.className = cardClass;
                    card.dataset.id = membresia.id_tipo_membresia; // Guardamos el ID aquí

                    card.innerHTML = `
                        <div class="card-header">
                            ${badge}
                            <h2 class="membership-title">${membresia.nombre}</h2>
                            <p class="membership-description">${membresia.descripcion}</p>
                        </div>
                        <div class="card-price">
                            <span class="price-value">$${parseFloat(membresia.precio).toFixed(2)}</span>
                            <span class="price-period">${membresia.periodo}</span>
                        </div>
                        <ul class="membership-features">
                            ${caracteristicasHtml}
                        </ul>
                        <button class="cta-button select-plan-btn">
                            Elegir Plan
                        </button>
                    `;
                    wrapper.appendChild(card);
                });
            } else {
                wrapper.innerHTML = '<p>No hay membresías disponibles en este momento.</p>';
            }
        })
        .catch(error => {
            console.error('Error al cargar las membresías:', error);
            Swal.fire({
                title: 'Error al Cargar',
                text: 'Ocurrió un error al cargar la información de las membresías. Intenta más tarde.',
                icon: 'error',
                confirmButtonText: 'Aceptar',
                background: '#1a1a1a',
                color: '#fff'
            });
            wrapper.innerHTML = '<p>Ocurrió un error al cargar la información.</p>';
        });
});