document.addEventListener('DOMContentLoaded', () => {
    const body = document.body;
    const appShell = document.getElementById('appShell');
    const sidebarCollapseToggle = document.getElementById('sidebarCollapseToggle');
    const themeToggle = document.getElementById('themeToggle');
    const loadingOverlay = document.getElementById('loadingOverlay');

    const applySidebarCollapsed = () => {
        if (!appShell || !sidebarCollapseToggle) return;
        const collapsed = appShell.classList.contains('sidebar-collapsed');
        sidebarCollapseToggle.setAttribute('aria-expanded', String(!collapsed));
        sidebarCollapseToggle.setAttribute('aria-label', collapsed ? 'Expand sidebar' : 'Collapse sidebar');
    };

    if (appShell) {
        appShell.classList.remove('sidebar-collapsed');
        localStorage.setItem('hms-sidebar-collapsed', '0');
    }
    applySidebarCollapsed();

    if (appShell && sidebarCollapseToggle) {
        sidebarCollapseToggle.addEventListener('click', () => {
            appShell.classList.toggle('sidebar-collapsed');
            localStorage.setItem('hms-sidebar-collapsed', appShell.classList.contains('sidebar-collapsed') ? '1' : '0');
            applySidebarCollapsed();
        });
    }

    const storedTheme = localStorage.getItem('hms-theme');
    if (storedTheme) {
        body.setAttribute('data-bs-theme', storedTheme);
    }

    if (themeToggle) {
        themeToggle.addEventListener('click', () => {
            const current = body.getAttribute('data-bs-theme') || 'light';
            const next = current === 'light' ? 'dark' : 'light';
            body.setAttribute('data-bs-theme', next);
            localStorage.setItem('hms-theme', next);
        });
    }

    document.querySelectorAll('form').forEach((form) => {
        form.classList.add('needs-validation');
        form.setAttribute('novalidate', 'novalidate');

        form.querySelectorAll('input, select, textarea').forEach((field) => {
            if (!field.classList.contains('btn')) {
                field.classList.add('form-control');
            }
        });

        form.addEventListener('submit', (event) => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            } else if (loadingOverlay) {
                loadingOverlay.classList.remove('d-none');
            }
            form.classList.add('was-validated');
        });
    });

    document.querySelectorAll('table').forEach((table) => {
        const rows = Array.from(table.querySelectorAll('tr'));
        if (rows.length <= 1) {
            const empty = document.createElement('div');
            empty.className = 'empty-state';
            empty.innerHTML = '<i class="fa-regular fa-folder-open"></i><p class="mb-0">No records found.</p>';
            table.parentElement?.appendChild(empty);
            return;
        }

        table.classList.add('table', 'table-hover', 'align-middle');
        const wrapper = document.createElement('div');
        wrapper.className = 'table-shell mb-3';
        table.parentNode.insertBefore(wrapper, table);
        wrapper.appendChild(table);

        const toolbar = document.createElement('div');
        toolbar.className = 'table-toolbar d-flex flex-wrap justify-content-between align-items-center gap-2';
        toolbar.innerHTML = `
            <input type="search" class="form-control form-control-sm table-search" style="max-width:260px" placeholder="Search table...">
            <div class="small text-muted table-meta"></div>
        `;
        wrapper.insertBefore(toolbar, table);

        const allDataRows = Array.from(table.querySelectorAll('tr')).slice(1);
        const pageSize = 8;
        let currentPage = 1;
        let filteredRows = [...allDataRows];

        const tbody = table.tBodies[0] || table;
        const pager = document.createElement('div');
        pager.className = 'd-flex justify-content-end p-2 bg-white border-top';
        wrapper.appendChild(pager);

        const render = () => {
            allDataRows.forEach((row) => {
                row.style.display = 'none';
            });

            const totalPages = Math.max(1, Math.ceil(filteredRows.length / pageSize));
            if (currentPage > totalPages) {
                currentPage = totalPages;
            }
            const start = (currentPage - 1) * pageSize;
            const end = start + pageSize;

            filteredRows.slice(start, end).forEach((row) => {
                row.style.display = '';
                tbody.appendChild(row);
            });

            toolbar.querySelector('.table-meta').textContent = `${filteredRows.length} records`;
            pager.innerHTML = `
                <button class="btn btn-sm btn-outline-secondary me-2" ${currentPage === 1 ? 'disabled' : ''} data-page="prev">Prev</button>
                <span class="small align-self-center">Page ${currentPage} of ${totalPages}</span>
                <button class="btn btn-sm btn-outline-secondary ms-2" ${currentPage === totalPages ? 'disabled' : ''} data-page="next">Next</button>
            `;
        };

        toolbar.querySelector('.table-search').addEventListener('input', (event) => {
            const query = event.target.value.toLowerCase();
            filteredRows = allDataRows.filter((row) => row.textContent.toLowerCase().includes(query));
            currentPage = 1;
            render();
        });

        pager.addEventListener('click', (event) => {
            const button = event.target.closest('button[data-page]');
            if (!button) return;
            if (button.dataset.page === 'prev') currentPage -= 1;
            if (button.dataset.page === 'next') currentPage += 1;
            render();
        });

        const headers = Array.from(table.querySelectorAll('th'));
        headers.forEach((header, index) => {
            header.style.cursor = 'pointer';
            header.title = 'Sort';
            header.addEventListener('click', () => {
                const ascending = !header.classList.contains('sorted-asc');
                headers.forEach((h) => h.classList.remove('sorted-asc', 'sorted-desc'));
                header.classList.add(ascending ? 'sorted-asc' : 'sorted-desc');
                filteredRows.sort((a, b) => {
                    const aText = (a.children[index]?.textContent || '').trim();
                    const bText = (b.children[index]?.textContent || '').trim();
                    return ascending ? aText.localeCompare(bText, undefined, { numeric: true }) : bText.localeCompare(aText, undefined, { numeric: true });
                });
                render();
            });
        });

        render();
    });

    const editMedicalModalElement = document.getElementById('editMedicalModal');
    if (editMedicalModalElement) {
        const editModal = bootstrap.Modal.getOrCreateInstance(editMedicalModalElement);
        const idInput = document.getElementById('editMedicalId');
        const nameInput = document.getElementById('editMedicalName');
        const categoryInput = document.getElementById('editMedicalCategory');
        const unitInput = document.getElementById('editMedicalUnit');
        const stockInput = document.getElementById('editMedicalStock');
        const priceInput = document.getElementById('editMedicalPrice');

        document.addEventListener('click', (event) => {
            const trigger = event.target.closest('.btn-edit-medical');
            if (!trigger) return;
            if (idInput) idInput.value = trigger.dataset.id || '';
            if (nameInput) nameInput.value = trigger.dataset.name || '';
            if (categoryInput) categoryInput.value = trigger.dataset.category || '';
            if (unitInput) unitInput.value = trigger.dataset.unit || '';
            if (stockInput) stockInput.value = trigger.dataset.stock || '0';
            if (priceInput) priceInput.value = trigger.dataset.price || '0';
            editModal.show();
        });
    }

    const deleteModalElement = document.getElementById('deleteConfirmModal');
    if (deleteModalElement) {
        const deleteModal = bootstrap.Modal.getOrCreateInstance(deleteModalElement);
        const deleteForm = document.getElementById('deleteConfirmForm');
        const deleteIdInput = document.getElementById('deleteConfirmId');
        const deleteText = document.getElementById('deleteConfirmText');

        document.addEventListener('click', (event) => {
            const trigger = event.target.closest('.btn-delete');
            if (!trigger) return;
            event.preventDefault();
            if (deleteForm && deleteIdInput && deleteText) {
                deleteForm.action = trigger.dataset.action || window.location.pathname;
                deleteIdInput.value = trigger.dataset.id || '';
                deleteText.textContent = trigger.dataset.message || 'Are you sure you want to delete this record?';
                deleteModal.show();
            }
        });
    }

    const statIcons = [
        'fa-solid fa-users',
        'fa-solid fa-hospital-user',
        'fa-solid fa-bed-pulse',
        'fa-solid fa-file-invoice-dollar',
        'fa-solid fa-vials',
        'fa-solid fa-capsules',
    ];
    document.querySelectorAll('.cards .card').forEach((card, index) => {
        if (card.querySelector('.stat-head')) return;
        const heading = card.querySelector('h3');
        const value = card.querySelector('p');
        if (!heading || !value) return;

        const head = document.createElement('div');
        head.className = 'stat-head d-flex align-items-center justify-content-between mb-2';
        head.innerHTML = `
            <h3 class="mb-0">${heading.textContent}</h3>
            <span class="rounded-circle d-inline-flex align-items-center justify-content-center" style="width:34px;height:34px;background:rgba(13,110,253,.12);color:#0d6efd;">
                <i class="${statIcons[index % statIcons.length]}"></i>
            </span>
        `;
        heading.replaceWith(head);
        value.classList.add('stat-value');
    });

    const toastElements = document.querySelectorAll('.toast');
    toastElements.forEach((toastEl) => {
        const toast = bootstrap.Toast.getOrCreateInstance(toastEl, { delay: 3500 });
        toast.show();
    });
});
