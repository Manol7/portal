import { texts } from './config.js';

// Przełącznik widoczności hasła na stronie logowania.
export const initPasswordToggle = () => {
    const toggle = document.querySelector('[data-password-toggle]');
    const input = document.querySelector('#password');
    if (!toggle || !input) {
        return;
    }

    toggle.addEventListener('click', () => {
        const isVisible = input.type === 'text';
        input.type = isVisible ? 'password' : 'text';
        toggle.textContent = isVisible ? texts.showPassword : texts.hidePassword;
        toggle.setAttribute('aria-label', isVisible ? texts.showPasswordAria : texts.hidePasswordAria);
    });
};
