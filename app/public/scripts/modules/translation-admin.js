// Wyszukiwarka wierszy tabeli tłumaczeń (dopasowanie po atrybucie data-search).
export const initTranslationAdmin = () => {
    const search = document.querySelector('[data-translation-search]');
    if (!search) {
        return;
    }

    search.addEventListener('input', () => {
        const query = search.value.trim().toLowerCase();
        let visible = 0;

        document.querySelectorAll('[data-translation-row]').forEach((row) => {
            const match = row.dataset.search.includes(query);
            row.hidden = !match;
            visible += match ? 1 : 0;
        });

        document.querySelector('[data-translation-empty]').hidden = visible > 0;
    });
};
