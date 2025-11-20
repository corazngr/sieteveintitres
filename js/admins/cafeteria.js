document.addEventListener('DOMContentLoaded', function () {
    // --- SELECCIÓN DE ELEMENTOS ---
    const saleForm = document.getElementById('sale-form');
    const productNameInput = document.getElementById('productName');
    const quantityInput = document.getElementById('quantity');
    const unitPriceInput = document.getElementById('unitPrice');
    const saleTotalSpan = document.getElementById('saleTotal');
    const quickProductBtns = document.querySelectorAll('.quick-product-btn'); // Vuelve a seleccionar los botones fijos
    const salesTableBody = document.querySelector('.sales-table tbody');
    const exportPdfBtn = document.getElementById('exportPdfBtn');
    const filterBtns = document.querySelectorAll('.filter-btn');

    const swalDark = {
        background: '#1a1a1a',
        color: '#fff'
    };

    // Lógica para menú móvil
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const sidebar = document.querySelector('.sidebar');

    // Overlay
    const sidebarOverlay = document.createElement('div');
    sidebarOverlay.className = 'sidebar-overlay';
    // ... (estilos del overlay igual que antes) ...
    document.body.appendChild(sidebarOverlay);
    sidebarOverlay.style.position = 'fixed';
    sidebarOverlay.style.top = '0';
    sidebarOverlay.style.left = '0';
    sidebarOverlay.style.width = '100%';
    sidebarOverlay.style.height = '100%';
    sidebarOverlay.style.background = 'rgba(0,0,0,0.5)';
    sidebarOverlay.style.zIndex = '1000';
    sidebarOverlay.style.display = 'none';

    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', () => {
            sidebar.classList.toggle('active');
            sidebarOverlay.style.display = sidebar.classList.contains('active') ? 'block' : 'none';
        });
    }

    sidebarOverlay.addEventListener('click', () => {
        sidebar.classList.remove('active');
        sidebarOverlay.style.display = 'none';
    });

    // --- FUNCIÓN PARA CALCULAR Y ACTUALIZAR EL TOTAL ---
    function updateTotal() {
        const quantity = parseFloat(quantityInput.value) || 0;
        const unitPrice = parseFloat(unitPriceInput.value) || 0;
        const total = quantity * unitPrice;
        saleTotalSpan.textContent = `$${total.toFixed(2)}`;
    }

    // --- FUNCIÓN PARA OBTENER Y RENDERIZAR VENTAS ---
    async function fetchAndRenderSales(filtro = 'day') {
        salesTableBody.innerHTML = '<tr><td colspan="4">Cargando ventas...</td></tr>';
        try {
            let url = `/sieteveintitres/php/admins/gestionar_cafeteria.php?filtro=${filtro}`;
            url += `&cache_buster=${new Date().getTime()}`; // Evita la caché
            const response = await fetch(url);
            const result = await response.json();

            salesTableBody.innerHTML = '';
            if (result.success && result.data.length > 0) {
                result.data.forEach(venta => {
                    const time = new Date(venta.created_at).toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' });
                    const rowHTML = `
                        <tr>
                            <td>${time}</td>
                            <td>${venta.cantidad} x ${venta.producto}</td>
                            <td class="text-right">$${parseFloat(venta.total).toFixed(2)}</td>
                            <td>${venta.vendedor}</td>
                        </tr>
                    `;
                    salesTableBody.innerHTML += rowHTML;
                });
            } else {
                salesTableBody.innerHTML = '<tr><td colspan="4">No hay ventas para este periodo.</td></tr>';
            }
        } catch (error) {
            console.error('Error:', error);
            salesTableBody.innerHTML = '<tr><td colspan="4">Error al cargar las ventas.</td></tr>';
            Swal.fire({
                title: 'Error al Cargar Ventas',
                text: 'No se pudieron cargar las ventas. Revisa tu conexión.',
                icon: 'error',
                confirmButtonText: 'Aceptar',
                ...swalDark
            });
        }
    }

    // --- LÓGICA DEL FORMULARIO DE GASTOS ---
    const expenseForm = document.getElementById('expense-form');

    if (expenseForm) {
        expenseForm.addEventListener('submit', async (e) => {
            e.preventDefault(); // Prevenimos el envío tradicional del formulario

            const formData = new FormData(expenseForm);

            // Validación simple
            if (!formData.get('descripcion') || !formData.get('monto') || formData.get('monto') <= 0) {
                Swal.fire({
                    title: 'Campos Incompletos',
                    text: 'Por favor, completa todos los campos con valores válidos.',
                    icon: 'warning',
                    confirmButtonText: 'Entendido',
                    ...swalDark
                });
                return;
            }

            // Opcional: Deshabilitar el botón para evitar doble envío
            const saveButton = expenseForm.querySelector('#btn-save-expense');
            saveButton.disabled = true;
            saveButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Guardando...';

            try {
                const response = await fetch('/sieteveintitres/php/admins/gasto_cafeteria.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                if (result.success) {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: result.message,
                        showConfirmButton: false,
                        timer: 2000,
                        ...swalDark
                    });
                    expenseForm.reset();
                } else {
                    Swal.fire({
                        title: 'Error al Guardar',
                        text: result.message,
                        icon: 'error',
                        confirmButtonText: 'Entendido',
                        ...swalDark
                    });
                }
            } catch (error) {
                console.error('Error al guardar el gasto:', error);
                // --- ¡CAMBIO 4: Notificación de error de conexión! ---
                Swal.fire({
                    title: 'Error de Conexión',
                    text: 'No se pudo guardar el gasto. Revisa tu conexión.',
                    icon: 'error',
                    confirmButtonText: 'Aceptar',
                    ...swalDark
                });
            } finally {
                saveButton.disabled = false;
                saveButton.innerHTML = '<i class="fa-solid fa-save"></i> Guardar Gasto';
            }
        });
    }

    // --- ASIGNACIÓN DE EVENTOS ---

    // 1. Llenar formulario con botones de venta rápida
    quickProductBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            productNameInput.value = btn.dataset.product;
            unitPriceInput.value = btn.dataset.price;
            quantityInput.value = 1;
            updateTotal();
            quantityInput.focus();
        });
    });

    // 2. Actualizar total al cambiar cantidad o precio
    quantityInput.addEventListener('input', updateTotal);
    unitPriceInput.addEventListener('input', updateTotal);

    // 3. Registrar venta
    saleForm.addEventListener('submit', async function (event) {
        event.preventDefault();

        const formData = new FormData();
        formData.append('producto', productNameInput.value);
        formData.append('cantidad', quantityInput.value);
        formData.append('precio_unitario', unitPriceInput.value);

        const submitBtn = saleForm.querySelector('button[type="submit"]');
        submitBtn.disabled = true;

        try {
            const response = await fetch('/sieteveintitres/php/admins/registrar_ventacafe.php', { method: 'POST', body: formData });
            const result = await response.json();
            if (result.success) {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: result.message, // "¡Venta registrada!"
                    showConfirmButton: false,
                    timer: 1500, // Más rápido porque es una acción repetitiva
                    ...swalDark
                });
                saleForm.reset();
                updateTotal();
                productNameInput.focus();
                fetchAndRenderSales(document.querySelector('.filter-btn.active').dataset.filter);
            } else {
                Swal.fire({
                    title: 'Error al Registrar Venta',
                    text: result.message,
                    icon: 'error',
                    confirmButtonText: 'Entendido',
                    ...swalDark
                });
            }
        } catch (error) {
            console.error('Error:', error);
            // --- ¡CAMBIO 6: Notificación de error de conexión! ---
            Swal.fire({
                title: 'Error de Conexión',
                text: 'No se pudo registrar la venta. Revisa tu conexión.',
                icon: 'error',
                confirmButtonText: 'Aceptar',
                ...swalDark
            });
        } finally {
            submitBtn.disabled = false;
        }
    });

    // 4. Funcionalidad de filtros
    filterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            filterBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            fetchAndRenderSales(btn.dataset.filter);
        });
    });

    // 5. Funcionalidad de exportar a PDF
    exportPdfBtn.addEventListener('click', function () {
        // Primero, encontramos qué filtro está activo
        const activeFilterBtn = document.querySelector('.filter-btn.active');
        const filtro = activeFilterBtn ? activeFilterBtn.dataset.filter : 'day';

        // Construimos la URL del script PHP con el filtro seleccionado
        const url = `/sieteveintitres/php/admins/reporte_cafe.php?filtro=${filtro}`;

        // Le decimos al navegador que vaya a esa URL, lo que iniciará la descarga del PDF
        window.location.href = url;
    });

    // --- CARGA INICIAL ---
    // Carga las ventas de 'Hoy' al entrar a la página
    fetchAndRenderSales('day');
});