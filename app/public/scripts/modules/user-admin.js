import { roles, texts } from './config.js';
import { showToast } from './toast.js';

const ajaxHeaders = { 'X-Requested-With': 'XMLHttpRequest' };

// --- Wyszukiwarka użytkowników (po numerze, e-mailu i nazwie roli) ---------------------------
const search = document.querySelector('[data-user-search]');

const applySearch = () => {
    if (!search) {
        return;
    }

    const query = search.value.trim().toLowerCase();
    let visible = 0;

    document.querySelectorAll('[data-user-row]').forEach((row) => {
        const match = row.dataset.id === query.replace(/^#/, '')
            || row.dataset.email.includes(query)
            || row.dataset.roleLabel.includes(query);
        row.hidden = !match;
        visible += match ? 1 : 0;
    });

    const empty = document.querySelector('[data-search-empty]');
    if (empty) {
        empty.hidden = visible > 0 || query === '';
    }
};

// --- Zmiana roli -----------------------------------------------------------------------------
// Etykieta pokazuje bieżącą rolę, lista zawiera pozostałe role dostępne dla zalogowanego użytkownika.
const renderRoleOptions = (select, currentRole, available) => {
    select.replaceChildren();

    const current = new Option(roles[currentRole], '', true, true);
    current.disabled = true;
    current.hidden = true;
    select.append(current);

    Object.entries(roles)
        .filter(([role]) => role !== currentRole && available.has(role))
        .forEach(([role, label]) => select.append(new Option(label, role)));

    select.dataset.currentRole = currentRole;
    if (select.dataset.i18nKey) {
        select.dataset.i18nKey = `role.${currentRole}`;
    }
    select.value = '';

    const row = select.closest('[data-user-row]');
    if (row) {
        row.dataset.roleLabel = roles[currentRole].toLowerCase();
        applySearch();
    }
};

const bindRoleSelect = (select) => {
    select.addEventListener('change', async () => {
        const selectedRole = select.value;
        const available = new Set([select.dataset.currentRole, ...[...select.options].map((option) => option.value)]);
        // FormData musi powstać przed zablokowaniem pola, bo wyłączone pola nie są wysyłane.
        const body = new FormData(select.form);
        select.disabled = true;

        try {
            const response = await fetch(select.form.action, {
                method: 'POST',
                body,
                headers: ajaxHeaders,
            });

            let result;
            try {
                result = await response.json();
            } catch {
                throw new Error(texts.roleFailedRefresh);
            }
            if (!response.ok) {
                throw new Error(result.message || texts.roleFailed);
            }

            renderRoleOptions(select, selectedRole, available);
            showToast('success', result.message);
        } catch (error) {
            showToast('error', error instanceof Error ? error.message : texts.roleFailed);
            select.value = '';
        } finally {
            select.disabled = false;
        }
    });
};

// --- Usuwanie i edycja konta (okna dialogowe wypełniane danymi z przycisku wiersza) ----------
const initDelete = () => {
    const modal = document.getElementById('delete-user-modal');
    const form = modal?.querySelector('[data-delete-form]');

    document.querySelectorAll('[data-delete-open]').forEach((button) => {
        button.addEventListener('click', () => {
            form.action = button.dataset.deleteAction;
            form.querySelector('[data-delete-token]').value = button.dataset.deleteToken;
            modal.querySelector('[data-delete-email-label]').textContent = button.dataset.deleteEmail;
            modal.showModal();
            modal.querySelector('.admin-confirm-cancel').focus();
        });
    });
};

const initEdit = () => {
    const modal = document.getElementById('edit-user-modal');
    const form = modal?.querySelector('[data-edit-form]');

    document.querySelectorAll('[data-edit-open]').forEach((button) => {
        button.addEventListener('click', () => {
            form.reset();
            form.action = button.dataset.editAction;
            form.querySelector('[data-edit-token]').value = button.dataset.editToken;
            form.querySelector('[name="email"]').value = button.dataset.editEmail;
            modal.showModal();
            form.querySelector('[name="email"]').focus();
        });
    });

    form?.addEventListener('submit', (event) => {
        const password = form.querySelector('[name="password"]');
        const confirmation = form.querySelector('[name="password_confirmation"]');
        confirmation.setCustomValidity(password.value === confirmation.value ? '' : texts.passwordMismatch);

        if (!form.reportValidity()) {
            event.preventDefault();
        }
    });
};

export const initUserAdmin = () => {
    search?.addEventListener('input', applySearch);
    document.querySelectorAll('[data-role-select]').forEach(bindRoleSelect);
    initDelete();
    initEdit();
};
