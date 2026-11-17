/* Warranty Color : page de configuration (aperçu en direct, valeurs par défaut). */
(function () {
    'use strict';

    function init() {
        const form = document.getElementById('wc-config-form');
        if (!form) {
            return;
        }
        const preview = document.getElementById('wc-preview');
        const live = document.getElementById('wc-live');
        const days = form.elements.days_before_expiration;
        const colorValid = form.elements.color_valid;
        const colorWarning = form.elements.color_warning;
        const fullRow = form.elements.full_row;
        const stateBoxes = () => form.querySelectorAll('input[name="excluded_states[]"]');

        function refresh() {
            preview.classList.toggle('warranty-fullrow', fullRow.checked);
            for (const [state, input] of [['valid', colorValid], ['warning', colorWarning]]) {
                const tr = preview.querySelector('tr[data-warranty="' + state + '"]');
                tr.style.setProperty('--warranty-color', input.value);
                tr.querySelector('.warranty-badge').style.setProperty('--warranty-color', input.value);
                form.querySelector('[data-hex-for="' + input.name + '"]').textContent = input.value;
            }
            // Le texte « Expire dans N jours » de l'aperçu suit le délai saisi.
            const n = parseInt(days.value, 10);
            if (n > 0) {
                const text = form.dataset.warningLabel.replace('%d', n);
                const tr = preview.querySelector('tr[data-warranty="warning"]');
                const badge = tr.querySelector('.warranty-badge');
                tr.cells[1].textContent = text;
                badge.setAttribute('aria-label', text);
                badge.dataset.tip = text;
            }
        }

        form.addEventListener('input', () => {
            live.textContent = '';
            refresh();
        });
        document.getElementById('wc-reset').addEventListener('click', () => {
            days.value = form.dataset.defaultDays;
            colorValid.value = form.dataset.defaultValid;
            colorWarning.value = form.dataset.defaultWarning;
            fullRow.checked = form.dataset.defaultFullRow === '1';
            for (const box of stateBoxes()) {
                box.checked = false;
            }
            refresh();
            live.textContent = form.dataset.resetMessage;       // annoncé (aria-live) : rien n'est enregistré tant qu'on n'a pas validé
        });
        refresh();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
