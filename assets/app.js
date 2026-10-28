(() => {
    const toggle = document.querySelector('.nav-toggle');
    const menu = document.getElementById('main-navigation');
    if (toggle && menu) {
        toggle.hidden = false;
        toggle.addEventListener('click', () => {
            const open = toggle.getAttribute('aria-expanded') !== 'true';
            toggle.setAttribute('aria-expanded', String(open));
            menu.classList.toggle('is-open', open);
        });
        menu.addEventListener('keydown', event => {
            if (event.key === 'Escape') {
                menu.classList.remove('is-open');
                toggle.setAttribute('aria-expanded', 'false');
                toggle.focus();
            }
        });
    }

    document.querySelectorAll('form[method="post" i]').forEach(form => {
        let submitting = false;
        form.addEventListener('submit', event => {
            if (event.defaultPrevented) return;
            if (submitting) { event.preventDefault(); return; }
            submitting = true;
            // Aguarda a coleta do botão acionado para preservar seu nome e valor no POST.
            setTimeout(() => form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach(button => {
                button.disabled = true;
                button.setAttribute('aria-disabled', 'true');
            }), 0);
        });
        window.addEventListener('pageshow', event => {
            if (!event.persisted) return;
            submitting = false;
            form.querySelectorAll('[aria-disabled="true"]').forEach(button => {
                button.disabled = false;
                button.removeAttribute('aria-disabled');
            });
        });
    });
    document.querySelectorAll('.alert').forEach(alert => {
        alert.setAttribute('role', alert.classList.contains('alert-danger') ? 'alert' : 'status');
    });
})();
