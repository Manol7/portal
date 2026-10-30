// Okna dialogowe: [data-modal-open="id"], [data-modal-close], zamykanie kliknięciem w tło
// oraz automatyczne otwarcie przy [data-open-on-load="true"] (np. po błędach formularza).
export const initModals = () => {
    document.querySelectorAll('[data-modal-open]').forEach((button) => {
        button.addEventListener('click', () => {
            document.getElementById(button.dataset.modalOpen)?.showModal();
        });
    });

    document.querySelectorAll('[data-modal-close]').forEach((button) => {
        button.addEventListener('click', () => button.closest('dialog').close());
    });

    document.querySelectorAll('dialog').forEach((dialog) => {
        dialog.addEventListener('click', (event) => {
            if (event.target === dialog) {
                dialog.close();
            }
        });
    });

    document.querySelectorAll('dialog[data-open-on-load="true"]').forEach((dialog) => dialog.showModal());
};
