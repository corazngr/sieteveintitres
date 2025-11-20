// Espera a que el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    // Llama a la función para cargar los eventos públicos
    loadPublicEvents();
});

/**
 * Carga los eventos desde la base de datos y los muestra en la galería
 */
async function loadPublicEvents() {
    const gallery = document.querySelector('.events-grid');
    if (!gallery) return; // Si no encuentra la galería, no hace nada

    // Muestra un mensaje de carga
    gallery.innerHTML = '<p style="color: white; text-align: center; grid-column: 1 / -1;">Cargando eventos...</p>'; 

    try {
        // 1. Llama al MISMO script PHP que usa tu panel de admin
        const response = await fetch('/sieteveintitres/php/admins/obtener_evento.php'); 
        
        if (!response.ok) {
            throw new Error(`Error HTTP: ${response.status}`);
        }
        
        const eventos = await response.json();

        // 2. Comprueba si hay eventos
        if (eventos.length === 0) {
            gallery.innerHTML = '<p style="color: white; text-align: center; grid-column: 1 / -1;">Próximamente tendremos nuevos eventos.</p>';
            return;
        }

        gallery.innerHTML = ''; // Limpia el mensaje de "cargando"

        // 3. Crea el HTML para cada evento
        eventos.forEach(evento => {
            const eventItem = document.createElement('div');
            eventItem.className = 'event-item';
            
            // Usamos la misma estructura HTML que tenías
            eventItem.innerHTML = `
                <img src="${evento.ruta_imagen}" alt="${evento.titulo}" class="modal-img">
                <div class="event-overlay">
                    <h3>${evento.titulo}</h3>
                    <p>${evento.descripcion}</p>
                </div>
            `;
            gallery.appendChild(eventItem);
        });

        // 4. ¡MUY IMPORTANTE! 
        // Llama a la función que activa los modales DESPUÉS de crear los eventos
        initializeModalLogic();

    } catch (error) {
        console.error('Error al cargar eventos:', error);
        gallery.innerHTML = '<p style="color: #FF0080; text-align: center; grid-column: 1 / -1;">Error al cargar los eventos.</p>';
    }
}

/**
 * Esta es la lógica del modal que tenías en tu HTML.
 * La movemos aquí para que se ejecute DESPUÉS de que existan las imágenes.
 */
function initializeModalLogic() {
    const images = document.querySelectorAll('.modal-img');
    const modal = document.getElementById('imageModal');
    const modalImg = document.getElementById('modalImage');
    const captionText = document.getElementById('caption');
    const closeBtn = document.getElementsByClassName('close')[0];

    // Si no encuentra los elementos del modal, no hace nada
    if (!modal || !modalImg || !captionText || !closeBtn) return;

    images.forEach(img => {
        img.onclick = function () {
            modal.style.display = "block";
            modalImg.src = this.src;
            captionText.innerHTML = this.alt;
        }
    });

    closeBtn.onclick = function () {
        modal.style.display = "none";
    }

    modal.onclick = function (e) {
        if (e.target === modal) {
            modal.style.display = "none";
        }
    }
}