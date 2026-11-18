document.addEventListener('click', (event) => {
    const confirmButton = event.target.closest('[data-confirm]');

    if (confirmButton && !confirm(confirmButton.dataset.confirm || 'Are you sure?')) {
        event.preventDefault();
    }
});

const textSizeSelect = document.querySelector('#text-size');
const textSizeKey = 'student-concerns-admin-text-size';
const validTextSizes = ['normal', 'large', 'larger'];
const savedTextSize = localStorage.getItem(textSizeKey);

if (validTextSizes.includes(savedTextSize)) {
    document.documentElement.dataset.textSize = savedTextSize;
}

if (textSizeSelect) {
    textSizeSelect.value = validTextSizes.includes(savedTextSize) ? savedTextSize : 'normal';
    textSizeSelect.addEventListener('change', () => {
        const textSize = validTextSizes.includes(textSizeSelect.value) ? textSizeSelect.value : 'normal';
        document.documentElement.dataset.textSize = textSize;
        localStorage.setItem(textSizeKey, textSize);
    });
}

const sidebarToggle = document.querySelector('.menu-toggle');
const sidebarClose = document.querySelector('.sidebar-close');
const sidebarBackdrop = document.querySelector('.sidebar-backdrop');

function setSidebarOpen(isOpen) {
    document.body.classList.toggle('sidebar-open', isOpen);
    sidebarToggle?.setAttribute('aria-expanded', String(isOpen));
    sidebarToggle?.setAttribute('aria-label', isOpen ? 'Close navigation' : 'Open navigation');

    if (!isOpen && document.querySelector('.sidebar')?.contains(document.activeElement)) {
        sidebarToggle?.focus();
    }
}

sidebarToggle?.addEventListener('click', () => {
    setSidebarOpen(!document.body.classList.contains('sidebar-open'));
});

sidebarClose?.addEventListener('click', () => setSidebarOpen(false));

sidebarBackdrop?.addEventListener('click', () => setSidebarOpen(false));

document.querySelectorAll('.sidebar nav a').forEach((link) => {
    link.addEventListener('click', () => setSidebarOpen(false));
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        setSidebarOpen(false);
    }
});

const notificationToggle = document.querySelector('.notification-toggle');
const notificationDropdown = document.querySelector('#notification-dropdown');

function setNotificationDropdownOpen(isOpen) {
    if (!notificationToggle || !notificationDropdown) return;
    notificationDropdown.hidden = !isOpen;
    notificationToggle.setAttribute('aria-expanded', String(isOpen));
}

notificationToggle?.addEventListener('click', () => {
    setNotificationDropdownOpen(notificationDropdown.hidden);
});

document.addEventListener('click', (event) => {
    if (!event.target.closest('.notification-menu')) {
        setNotificationDropdownOpen(false);
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') setNotificationDropdownOpen(false);
});
