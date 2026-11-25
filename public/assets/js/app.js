/* KorlasSync — small, dependency-free interactions */
(() => {
    'use strict';

    const body = document.body;

    /* Sidebar (mobile) */
    const openSidebar = () => body.classList.add('sidebar-open');
    const closeSidebar = () => body.classList.remove('sidebar-open');

    document.querySelectorAll('[data-sidebar-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            body.classList.toggle('sidebar-open');
        });
    });

    document.querySelectorAll('[data-sidebar-close]').forEach((element) => {
        element.addEventListener('click', closeSidebar);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeSidebar();
        }
    });

    /* Dismissable alerts */
    document.querySelectorAll('[data-dismiss-alert]').forEach((button) => {
        button.addEventListener('click', () => {
            const alert = button.closest('.alert');
            if (alert) {
                alert.style.display = 'none';
            }
        });
    });

    /* Confirm destructive forms/links */
    document.querySelectorAll('[data-confirm]').forEach((element) => {
        const message = element.getAttribute('data-confirm');
        const handler = (event) => {
            if (!window.confirm(message || 'Yakin ingin melanjutkan?')) {
                event.preventDefault();
            }
        };

        if (element.tagName === 'FORM') {
            element.addEventListener('submit', handler);
        } else {
            element.addEventListener('click', handler);
        }
    });

    /* Password visibility */
    document.querySelectorAll('[data-password-toggle]').forEach((button) => {
        const input = document.getElementById(button.getAttribute('data-password-toggle'));
        if (!input) return;

        button.addEventListener('click', () => {
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            button.classList.toggle('is-visible', isPassword);
            button.setAttribute('aria-label', isPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
        });
    });

    /* Demo account shortcuts on the login page */
    document.querySelectorAll('[data-demo-email]').forEach((button) => {
        button.addEventListener('click', () => {
            const email = document.getElementById('email');
            const password = document.getElementById('password');

            if (email) email.value = button.getAttribute('data-demo-email') || '';
            if (password) password.value = button.getAttribute('data-demo-password') || 'password';

            if (email) {
                email.focus();
                email.dispatchEvent(new Event('input', { bubbles: true }));
            }
        });
    });

    /* Auto-submit filter selects */
    document.querySelectorAll('[data-auto-submit]').forEach((select) => {
        select.addEventListener('change', () => select.form?.submit());
    });

    /* Searchable select enhancer */
    const initSearchableSelects = () => {
        document.querySelectorAll('select[data-searchable-select]').forEach((select) => {
            if (select.dataset.sselectReady === '1') return;
            select.dataset.sselectReady = '1';

            const placeholder = select.getAttribute('data-placeholder') || 'Ketik untuk mencari...';
            const wrapper = document.createElement('div');
            wrapper.className = 'sselect';

            select.parentNode.insertBefore(wrapper, select);
            select.classList.add('sr-only');
            select.tabIndex = -1;
            wrapper.appendChild(select);

            const trigger = document.createElement('button');
            trigger.type = 'button';
            trigger.className = 'sselect-trigger';
            const labelSpan = document.createElement('span');
            labelSpan.className = 'sselect-label';
            trigger.appendChild(labelSpan);
            wrapper.appendChild(trigger);

            const panel = document.createElement('div');
            panel.className = 'sselect-panel';
            const searchWrap = document.createElement('div');
            searchWrap.className = 'sselect-search-wrap';
            const searchInput = document.createElement('input');
            searchInput.type = 'search';
            searchInput.className = 'sselect-search';
            searchInput.placeholder = placeholder;
            searchInput.autocomplete = 'off';
            searchWrap.appendChild(searchInput);
            panel.appendChild(searchWrap);

            const list = document.createElement('div');
            list.className = 'sselect-list';
            panel.appendChild(list);
            wrapper.appendChild(panel);

            const syncTriggerLabel = () => {
                const selectedOpt = select.options[select.selectedIndex];
                const text = selectedOpt ? selectedOpt.textContent.trim() : '';
                labelSpan.textContent = text || placeholder;
                trigger.classList.toggle('is-placeholder', !selectedOpt || selectedOpt.value === '');
            };

            const renderOptions = (query = '') => {
                const q = query.trim().toLowerCase();
                list.innerHTML = '';
                let count = 0;

                Array.from(select.options).forEach((opt, idx) => {
                    if (opt.disabled) return;
                    const text = opt.textContent.trim();
                    if (q !== '' && !text.toLowerCase().includes(q)) {
                        return;
                    }
                    count++;
                    const item = document.createElement('div');
                    item.className = 'sselect-option' + (select.selectedIndex === idx ? ' is-selected' : '');
                    item.textContent = text;
                    item.addEventListener('click', () => {
                        select.selectedIndex = idx;
                        select.dispatchEvent(new Event('change', { bubbles: true }));
                        syncTriggerLabel();
                        wrapper.classList.remove('is-open');
                    });
                    list.appendChild(item);
                });

                if (count === 0) {
                    const empty = document.createElement('div');
                    empty.className = 'sselect-empty';
                    empty.textContent = 'Tidak ada hasil yang cocok.';
                    list.appendChild(empty);
                }
            };

            trigger.addEventListener('click', () => {
                const willOpen = !wrapper.classList.contains('is-open');
                document.querySelectorAll('.sselect.is-open').forEach((el) => el.classList.remove('is-open'));
                if (willOpen) {
                    wrapper.classList.add('is-open');
                    searchInput.value = '';
                    renderOptions('');
                    setTimeout(() => searchInput.focus(), 10);
                }
            });

            searchInput.addEventListener('input', () => {
                renderOptions(searchInput.value);
            });

            searchInput.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    wrapper.classList.remove('is-open');
                    trigger.focus();
                }
            });

            select.addEventListener('change', syncTriggerLabel);
            syncTriggerLabel();
        });
    };

    initSearchableSelects();

    document.addEventListener('click', (event) => {
        if (!event.target.closest('.sselect')) {
            document.querySelectorAll('.sselect.is-open').forEach((el) => el.classList.remove('is-open'));
        }
    });

    /* Searchable checklist enhancer */
    document.querySelectorAll('[data-checklist-box]').forEach((box) => {
        const searchInput = box.querySelector('[data-checklist-search]');
        const items = Array.from(box.querySelectorAll('[data-checklist-item]'));
        const emptyState = box.querySelector('[data-checklist-empty]');
        const counter = box.querySelector('[data-checklist-counter]');
        const selectVisibleBtn = box.querySelector('[data-checklist-select-visible]');
        const clearBtn = box.querySelector('[data-checklist-clear]');

        const updateCounter = () => {
            if (!counter) return;
            const checkedCount = items.filter((item) => {
                const cb = item.querySelector('input[type="checkbox"]');
                return cb && cb.checked;
            }).length;
            counter.textContent = `${checkedCount} dipilih`;
        };

        const filterItems = () => {
            const q = (searchInput?.value || '').trim().toLowerCase();
            let visibleCount = 0;

            items.forEach((item) => {
                const haystack = (item.getAttribute('data-search-text') || item.textContent || '').toLowerCase();
                const match = q === '' || haystack.includes(q);
                item.style.display = match ? '' : 'none';
                if (match) visibleCount++;
            });

            if (emptyState) {
                emptyState.style.display = visibleCount === 0 ? '' : 'none';
            }
        };

        items.forEach((item) => {
            const cb = item.querySelector('input[type="checkbox"]');
            cb?.addEventListener('change', updateCounter);
        });

        searchInput?.addEventListener('input', filterItems);

        selectVisibleBtn?.addEventListener('click', () => {
            items.forEach((item) => {
                if (item.style.display !== 'none') {
                    const cb = item.querySelector('input[type="checkbox"]');
                    if (cb) cb.checked = true;
                }
            });
            updateCounter();
        });

        clearBtn?.addEventListener('click', () => {
            items.forEach((item) => {
                const cb = item.querySelector('input[type="checkbox"]');
                if (cb) cb.checked = false;
            });
            updateCounter();
        });

        updateCounter();
    });
})();
