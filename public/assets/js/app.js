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
})();
