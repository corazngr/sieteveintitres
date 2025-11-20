document.addEventListener('DOMContentLoaded', () => {
    const row1 = document.getElementById('row1');
    const row2 = document.getElementById('row2');
    const row3 = document.getElementById('row3');
    const confirmButton = document.getElementById('confirm-button');
    let selectedSeat = null;

    const urlParams = new URLSearchParams(window.location.search);
    const idHorario = urlParams.get('id_horario');

    if (!idHorario) {
        document.querySelector('.container').innerHTML = '<h2>Error</h2><p>No se ha especificado una clase para reservar.</p>';
        return;
    }

    fetch(`/sieteveintitres/php/publico/obtener_estado_bicis.php?id_horario=${idHorario}`)
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                throw new Error(data.message || 'No se pudo cargar el estado de las bicis.');
            }

            const ocupadas = data.ocupadas;
            const fueraDeServicio = data.fuera_de_servicio;

            for (let i = 1; i <= data.total_bicis; i++) {

                let bikeStatus = 'available';
                if (ocupadas.includes(i)) {
                    bikeStatus = 'occupied';
                } else if (fueraDeServicio.includes(i)) {
                    bikeStatus = 'maintenance';
                }

                const seat = document.createElement('div');
                seat.className = `seat ${bikeStatus}`;
                seat.dataset.id = i;
                seat.innerHTML = `<i class="fas fa-bicycle bike-icon"></i><span class="bike-number">${i}</span>`;

                if (bikeStatus === 'available') {

                    seat.addEventListener('click', () => {
                        if (selectedSeat) {
                            selectedSeat.classList.remove('selected');
                        }

                        if (selectedSeat === seat) {
                            selectedSeat = null;
                            confirmButton.style.display = 'none';
                        } else {
                            seat.classList.add('selected');
                            selectedSeat = seat;
                            confirmButton.style.display = 'block';
                        }
                    });
                }

                if (i <= 5) row1.appendChild(seat);
                else if (i <= 13) row2.appendChild(seat);
                else row3.appendChild(seat);
            }
        })
        .catch(error => {
            document.querySelector('.container').innerHTML = `<h2>Error</h2><p>${error.message}</p>`;
        });

    confirmButton.addEventListener('click', async () => {
        if (!selectedSeat) {
            alert('Por favor, selecciona una bici.');
            return;
        }

        const idBici = selectedSeat.dataset.id;
        confirmButton.disabled = true;
        confirmButton.textContent = 'PROCESANDO...';

        const formData = new FormData();
        formData.append('id_horario', idHorario);
        formData.append('id_bici', idBici);

        try {
            const response = await fetch('/sieteveintitres/php/publico/crear_reservacion.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.success) {
                await Swal.fire({
                    title: '¡Reservación Confirmada!',
                    text: 'Nos vemos en el studio.',
                    icon: 'success',
                    confirmButtonText: 'Genial',
                    background: '#1a1a1a', 
                    color: '#fff' 
                });

                window.location.href = '/sieteveintitres/html/publico/miusuario.html';

            } else {
                Swal.fire({
                    title: 'Oops... No se pudo reservar',
                    text: result.message,
                    icon: 'error',
                    confirmButtonText: 'Entendido',
                    background: '#1a1a1a',
                    color: '#fff'
                });

                confirmButton.disabled = false;
                confirmButton.textContent = 'RESERVAR';

                if (result.message.includes('bici ya no está disponible')) {
                    location.reload();
                }
            }
        } catch (error) {
            Swal.fire({
                title: 'Error de Conexión',
                text: 'No se pudo conectar con el servidor. Revisa tu conexión a internet e intenta de nuevo.',
                icon: 'error',
                confirmButtonText: 'Aceptar',
                background: '#1a1a1a',
                color: '#fff'
            });

            confirmButton.disabled = false;
            confirmButton.textContent = 'RESERVAR';
        }
    });
});