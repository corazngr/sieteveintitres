document.addEventListener('DOMContentLoaded', function () {

    // --- SELECTORES PRINCIPALES ---
    const summaryCards = {
        incomeToday: document.querySelector('.summary-card.income span'),
        expenseToday: document.querySelector('.summary-card.expense span'),
        netBalanceToday: document.querySelector('.summary-card.net-balance span'),
        monthBalance: document.querySelector('.summary-card.month-balance span')
    };
    const transactionsTbody = document.querySelector('.transactions-table tbody');

    // Modales
    const transactionModal = document.getElementById('transactionModal');
    const receiptModal = document.getElementById('receiptModal');

    // Botones y Formularios
    const btnAddExpense = document.getElementById('btn-add-expense');
    const btnAddIncome = document.getElementById('btn-add-income');
    const transactionForm = transactionModal.querySelector('form');
    const btnSaveTransaction = transactionModal.querySelector('.btn-primary');

    // Filtros
    const monthSelector = document.getElementById('monthSelector');
    const yearSelector = document.getElementById('yearSelector');
    const typeFilter = document.getElementById('typeFilter');
    const btnGenerateReport = document.getElementById('btn-generate-report');

    let allTransactions = [];
    const swalDark = {
        background: '#1a1a1a',
        color: '#fff'
    };

    // Lógica para menú móvil
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const sidebar = document.querySelector('.sidebar');

    const overlay = document.createElement('div');
    overlay.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:1000;display:none;';
    document.body.appendChild(overlay);

    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', () => {
            sidebar.classList.toggle('active');
            overlay.style.display = sidebar.classList.contains('active') ? 'block' : 'none';
        });
    }

    overlay.addEventListener('click', () => {
        sidebar.classList.remove('active');
        overlay.style.display = 'none';
    });

    // --- FUNCIÓN PARA FORMATEAR MONEDA ---
    const formatCurrency = (amount) => new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(amount || 0);

    // --- LÓGICA PRINCIPAL DE CARGA DE DATOS ---
    async function fetchFinancialData(month, year) {
        try {
            // Enviamos el mes y año al PHP para obtener solo los datos necesarios
            const response = await fetch(`/sieteveintitres/php/admins/gestionar_finanzas.php?month=${month}&year=${year}`);
            const data = await response.json();

            if (!data.success) throw new Error(data.message);

            // 1. Actualizar tarjetas de resumen
            summaryCards.incomeToday.textContent = formatCurrency(data.summary.ingresos_hoy);
            summaryCards.expenseToday.textContent = formatCurrency(data.summary.gastos_hoy);
            summaryCards.netBalanceToday.textContent = formatCurrency(data.summary.saldo_hoy);
            summaryCards.monthBalance.textContent = formatCurrency(data.summary.saldo_mes);

            // 2. Guardar y renderizar la tabla de transacciones
            allTransactions = data.transactions;
            applyFilters(); // Aplicamos filtros (como el de tipo) antes de renderizar

            // 3. Llenar el selector de años (solo la primera vez)
            if (yearSelector.options.length === 0) {
                const currentYear = new Date().getFullYear();
                yearSelector.innerHTML = ''; // Limpiamos por si acaso
                data.available_years.forEach(item => {
                    const option = new Option(item.anio, item.anio);
                    yearSelector.add(option);
                });
                // Si el año actual no tiene transacciones, lo añadimos a la lista
                if (!data.available_years.some(item => item.anio == currentYear)) {
                    const option = new Option(currentYear, currentYear);
                    yearSelector.add(option, yearSelector.options[0]);
                }
            }

            // 4. Asegurar que los selectores muestren la fecha correcta
            monthSelector.value = month;
            yearSelector.value = year;

        } catch (error) {
            console.error('Error al cargar datos financieros:', error);
            transactionsTbody.innerHTML = `<tr><td colspan="6" class="text-center">Error al cargar los datos. Verifique la conexión.</td></tr>`;
            Swal.fire({
                title: 'Error al Cargar Datos',
                text: 'No se pudieron cargar los datos financieros. Revisa tu conexión.',
                icon: 'error',
                confirmButtonText: 'Aceptar',
                ...swalDark
            });
        }
    }

    // --- FUNCIÓN PARA PINTAR LA TABLA ---
    function renderTransactions(transactions) {
        transactionsTbody.innerHTML = '';
        if (transactions.length === 0) {
            transactionsTbody.innerHTML = `<tr><td colspan="6" class="text-center">No hay transacciones que coincidan con la búsqueda.</td></tr>`;
            return;
        }
        transactions.forEach(tx => {
            const isIncome = tx.tipo === 'Ingreso';
            const date = new Date(tx.fecha_completa).toLocaleString('es-MX', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
            const rowHTML = `
                <tr data-id="${tx.id}">
                    <td>${date}</td>
                    <td>${tx.descripcion}</td>
                    <td><span class="type-badge ${isIncome ? 'income' : 'expense'}">${tx.tipo}</span></td>
                    <td class="text-right ${isIncome ? 'amount-income' : 'amount-expense'}">${isIncome ? '+' : '-'}${formatCurrency(tx.monto)}</td>
                    <td>${tx.responsable}</td>
                    <td><button class="action-btn-table receipt-btn"><i class="fa-solid fa-receipt"></i></button></td>
                </tr>`;
            transactionsTbody.insertAdjacentHTML('beforeend', rowHTML);
        });
    }

    // --- LÓGICA DE FILTRADO (Ahora solo filtra por tipo) ---
    function applyFilters() {
        let filteredTransactions = allTransactions;
        const type = typeFilter.value;
        if (type !== 'all') {
            const filterType = type === 'income' ? 'Ingreso' : 'Gasto';
            filteredTransactions = filteredTransactions.filter(tx => tx.tipo === filterType);
        }
        renderTransactions(filteredTransactions);
    }

    // --- MANEJADORES DE EVENTOS ---
    function handleDateChange() {
        const selectedMonth = monthSelector.value;
        const selectedYear = yearSelector.value;
        fetchFinancialData(selectedMonth, selectedYear);
    }

    monthSelector.addEventListener('change', handleDateChange);
    yearSelector.addEventListener('change', handleDateChange);
    typeFilter.addEventListener('change', applyFilters);

    btnGenerateReport.addEventListener('click', function () {
        const month = monthSelector.value;
        const year = yearSelector.value;
        const type = typeFilter.value;

        const reportUrl = `/sieteveintitres/php/admins/reporte_finanzas.php?month=${month}&year=${year}&type=${type}`;
        window.open(reportUrl, '_blank');
    });

    const openModal = (modal) => modal.classList.add('visible');
    const closeModal = (modal) => modal.classList.remove('visible');

    [transactionModal, receiptModal].forEach(modal => {
        modal.querySelectorAll('.close-btn, .btn-secondary').forEach(btn => {
            btn.addEventListener('click', () => closeModal(modal));
        });
    });

    function setupTransactionModal(type) {
        transactionForm.reset();
        const isIncome = type === 'Ingreso';
        transactionModal.querySelector('#transactionModalTitle').textContent = `Registrar ${type}`;
        transactionModal.querySelector('#incomeTypeGroup').style.display = isIncome ? 'flex' : 'none';
        transactionModal.querySelector('#expenseTypeGroup').style.display = !isIncome ? 'flex' : 'none';
        let typeInput = transactionForm.querySelector('input[name="tipo"]');
        if (!typeInput) {
            typeInput = document.createElement('input');
            typeInput.type = 'hidden';
            typeInput.name = 'tipo';
            transactionForm.appendChild(typeInput);
        }
        typeInput.value = type;
        openModal(transactionModal);
    }

    btnAddIncome.addEventListener('click', () => setupTransactionModal('Ingreso'));
    btnAddExpense.addEventListener('click', () => setupTransactionModal('Gasto'));

    btnSaveTransaction.addEventListener('click', async () => {
        const formData = new FormData(transactionForm);

        const descripcion = formData.get('transDescription');
        const monto = formData.get('transAmount');
        if (!descripcion || !monto || monto <= 0) {
            Swal.fire({
                title: 'Campos Incompletos',
                text: 'Por favor, completa la descripción y un monto válido.',
                icon: 'warning',
                confirmButtonText: 'Entendido',
                ...swalDark
            });
            return;
        }

        formData.append('descripcion', formData.get('transDescription'));
        formData.append('monto', formData.get('transAmount'));
        formData.append('responsable', formData.get('transResponsible'));
        formData.append('categoria_ingreso', formData.get('tipo_ingreso'));
        formData.append('categoria_gasto', formData.get('tipo_egreso'));

        try {
            const response = await fetch('/sieteveintitres/php/admins/registrar_transaccion.php', { method: 'POST', body: formData });
            const result = await response.json();

            // --- ¡CAMBIO 3: Notificación de éxito o error! ---
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
                closeModal(transactionModal);
                handleDateChange(); // Recarga los datos
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
            console.error('Error al guardar la transacción:', error);
            // --- ¡CAMBIO 4: Notificación de error de conexión! ---
            Swal.fire({
                title: 'Error de Conexión',
                text: 'No se pudo guardar la transacción.',
                icon: 'error',
                confirmButtonText: 'Aceptar',
                ...swalDark
            });
        }
    });

    transactionsTbody.addEventListener('click', function (e) {
        const receiptBtn = e.target.closest('.receipt-btn');
        if (receiptBtn) {
            const row = receiptBtn.closest('tr');
            const transactionId = row.dataset.id;
            const transactionData = allTransactions.find(tx => tx.id == transactionId);
            if (transactionData) {
                document.getElementById('receiptDate').textContent = new Date(transactionData.fecha_completa).toLocaleString('es-MX');
                document.getElementById('receiptDescription').textContent = transactionData.descripcion;
                document.getElementById('receiptType').innerHTML = `<span class="type-badge ${transactionData.tipo === 'Ingreso' ? 'income' : 'expense'}">${transactionData.tipo} (${transactionData.categoria})</span>`;
                document.getElementById('receiptAmount').textContent = formatCurrency(transactionData.monto);
                document.getElementById('receiptResponsible').textContent = transactionData.responsable;
                openModal(receiptModal);
            }
        }
    });

    // --- CARGA INICIAL DE DATOS ---
    const today = new Date();
    const currentMonth = today.getMonth() + 1; // getMonth() es 0-11, por eso se suma 1
    const currentYear = today.getFullYear();
    fetchFinancialData(currentMonth, currentYear);
});