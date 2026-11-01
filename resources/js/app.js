const timeFormatter = new Intl.DateTimeFormat('id-ID', {
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
    hour12: false,
    timeZone: 'Asia/Jakarta',
});

const dateFormatter = new Intl.DateTimeFormat('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    timeZone: 'Asia/Jakarta',
});

function updateDashboardClock() {
    const now = new Date();
    const time = document.querySelector('[data-current-time]');
    const date = document.querySelector('[data-current-date]');

    if (time) {
        time.textContent = `${timeFormatter.format(now)} WIB`;
    }

    if (date) {
        date.textContent = dateFormatter.format(now);
    }
}

function initializeDashboardNavigation() {
    const toggle = document.querySelector('.menu-toggle');
    const navigation = document.querySelector('#primary-nav');

    if (!toggle || !navigation) {
        return;
    }

    toggle.addEventListener('click', () => {
        const isOpen = toggle.getAttribute('aria-expanded') === 'true';

        toggle.setAttribute('aria-expanded', String(!isOpen));
        navigation.classList.toggle('is-open', !isOpen);
    });

    navigation.addEventListener('click', (event) => {
        if (event.target instanceof Element && event.target.closest('.nav-link')) {
            toggle.setAttribute('aria-expanded', 'false');
            navigation.classList.remove('is-open');
        }
    });
}

updateDashboardClock();
window.setInterval(updateDashboardClock, 1000);
initializeDashboardNavigation();
