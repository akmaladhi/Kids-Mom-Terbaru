const menuToggle = document.querySelector('.menu-toggle');
const mainNav = document.querySelector('#main-nav');

if (menuToggle && mainNav) {
    menuToggle.addEventListener('click', () => {
        const isOpen = menuToggle.getAttribute('aria-expanded') === 'true';
        menuToggle.setAttribute('aria-expanded', String(!isOpen));
        mainNav.classList.toggle('is-open', !isOpen);
    });

    mainNav.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => {
            menuToggle.setAttribute('aria-expanded', 'false');
            mainNav.classList.remove('is-open');
        });
    });
}

document.querySelectorAll('.page-back, [data-history-back]').forEach((link) => {
    link.addEventListener('click', (event) => {
        if (link.classList.contains('page-back-static')) {
            return;
        }

        if (window.history.length > 1) {
            event.preventDefault();
            window.history.back();
            return;
        }

        if (link.tagName === 'BUTTON') {
            window.location.assign(link.dataset.fallback || 'index.php');
        }
    });
});

document.querySelectorAll('[data-print]').forEach((button) => {
    button.addEventListener('click', () => window.print());
});

document.querySelectorAll('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (!window.confirm(form.dataset.confirm)) {
            event.preventDefault();
        }
    });
});
