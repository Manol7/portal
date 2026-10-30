import { texts } from './config.js';

const AUTO_HIDE_MS = 6000;
const LEAVE_MS = 220;

const dismissToast = (toast) => {
    toast.classList.add('is-leaving');
    window.setTimeout(() => toast.remove(), LEAVE_MS);
};

// Toast znika po czasie albo po kliknięciu w dowolne miejsce.
const bindToast = (toast) => {
    toast.addEventListener('click', () => dismissToast(toast));
    window.setTimeout(() => dismissToast(toast), AUTO_HIDE_MS);
};

export const showToast = (type, message) => {
    const toast = document.createElement('div');
    toast.className = `app-toast app-toast--${type}`;
    toast.setAttribute('role', type === 'error' ? 'alert' : 'status');
    toast.dataset.toast = '';

    const indicator = document.createElement('span');
    indicator.className = 'app-toast-indicator';
    indicator.setAttribute('aria-hidden', 'true');

    const content = document.createElement('p');
    content.textContent = message;

    const close = document.createElement('button');
    close.className = 'app-toast-close';
    close.type = 'button';
    close.setAttribute('aria-label', texts.closeToast);
    close.textContent = '×';

    const timer = document.createElement('span');
    timer.className = 'app-toast-timer';
    timer.setAttribute('aria-hidden', 'true');

    toast.append(indicator, content, close, timer);
    document.querySelector('.app-toasts').append(toast);
    bindToast(toast);
};

export const initToasts = () => {
    document.querySelectorAll('[data-toast]').forEach(bindToast);
};
