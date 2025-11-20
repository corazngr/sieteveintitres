// Espera a que el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    loadPublicCoaches();
});

/**
 * Carga los perfiles de coaches desde la BD y los muestra en la galería
 */
async function loadPublicCoaches() {
    const grid = document.querySelector('.coaches-grid');
    if (!grid) return; // Si no encuentra la galería, no hace nada

    // Muestra un mensaje de carga
    grid.innerHTML = '<p style="color: white; text-align: center; grid-column: 1 / -1;">Cargando coaches...</p>'; 

    try {
        // 1. Llama al MISMO script PHP que usa tu panel de admin (el que tiene el JOIN)
        const response = await fetch('/sieteveintitres/php/admins/obtener_coaches.php'); 
        
        if (!response.ok) {
            throw new Error(`Error HTTP: ${response.status}`);
        }
        
        const coaches = await response.json();

        // 2. Comprueba si hay coaches
        if (coaches.length === 0) {
            grid.innerHTML = '<p style="color: white; text-align: center; grid-column: 1 / -1;">Próximamente tendremos perfiles de coaches.</p>';
            return;
        }

        grid.innerHTML = ''; // Limpia el mensaje de "cargando"

        // 3. Crea el HTML para cada coach
        coaches.forEach(coach => {
            const coachCard = document.createElement('div');
            coachCard.className = 'coach-card';

            // --- Construye los links de redes sociales (solo si existen) ---
            let socialHTML = '';
            if (coach.link_facebook) {
                socialHTML += `<a href="${coach.link_facebook}" target="_blank"><i class="fab fa-facebook-f"></i></a>`;
            }
            if (coach.link_instagram) {
                socialHTML += `<a href="${coach.link_instagram}" target="_blank"><i class="fab fa-instagram"></i></a>`;
            }
            // --- Fin de construcción de links ---

            // Usamos la misma estructura HTML que tenías
            coachCard.innerHTML = `
                <div class="coach-image-container">
                    <img class="front-image" src="${coach.ruta_img_perfil}" alt="Coach ${coach.nombre_coach} Perfil">
                    <img class="back-image" src="${coach.ruta_img_gustos}" alt="Coach ${coach.nombre_coach} Gustos">
                </div>
                <div class="coach-info">
                    <h3>${coach.nombre_coach}</h3>
                    <p class="specialty">${coach.especialidad}</p>
                    <p>${coach.descripcion}</p>
                    <div class="coach-social">
                        ${socialHTML}
                    </div>
                </div>
            `;
            grid.appendChild(coachCard);
        });

        // 4. ¡MUY IMPORTANTE! 
        // Llama a la función que activa tu modal DESPUÉS de crear los coaches
        initializeCoachModalLogic();

    } catch (error) {
        console.error('Error al cargar coaches:', error);
        grid.innerHTML = '<p style="color: #FF0080; text-align: center; grid-column: 1 / -1;">Error al cargar los perfiles.</p>';
    }
}

/**
 * Esta es la lógica del modal que tenías en tu HTML.
 * La movemos aquí para que se ejecute DESPUÉS de que existan las tarjetas.
 */
function initializeCoachModalLogic() {
    const modal = document.getElementById("imgModal");
    const modalContent = document.querySelector(".modal-content");
    const closeBtn = document.querySelector(".close");
    const prevBtn = document.querySelector(".prev");
    const nextBtn = document.querySelector(".next");

    if (!modal || !modalContent || !closeBtn || !prevBtn || !nextBtn) {
        console.error("Faltan elementos del modal en el HTML.");
        return;
    }

    let currentIndex = 0;
    let modalImages = [];

    // Seleccionar todos los contenedores de imágenes
    const containers = document.querySelectorAll(".coach-image-container");

    containers.forEach(container => {
        container.addEventListener("click", () => {
            // Tomar ambas imágenes de ese coach
            const imgs = container.querySelectorAll("img");
            modalImages = [];

            // Limpiar contenido anterior
            modalContent.querySelectorAll("img").forEach(el => el.remove());

            imgs.forEach((img, index) => {
                const clone = document.createElement("img");
                clone.src = img.src;
                if (index === 0) clone.classList.add("active");
                modalImages.push(clone);
                modalContent.insertBefore(clone, prevBtn);
            });

            currentIndex = 0;
            modal.style.display = "block";
        });
    });

    // Función para mostrar imagen
    function showImage(index) {
        modalImages.forEach(img => img.classList.remove("active"));
        modalImages[index].classList.add("active");
    }

    // Navegar entre imágenes
    prevBtn.onclick = () => {
        currentIndex = (currentIndex - 1 + modalImages.length) % modalImages.length;
        showImage(currentIndex);
    };

    nextBtn.onclick = () => {
        currentIndex = (currentIndex + 1) % modalImages.length;
        showImage(currentIndex);
    };

    // Cerrar modal
    closeBtn.onclick = () => modal.style.display = "none";
    modal.onclick = e => { if (e.target === modal) modal.style.display = "none"; };
}