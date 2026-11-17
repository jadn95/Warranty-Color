/* Warranty Color : page de configuration (aperçu en direct, valeurs par défaut). */
(function () {
    'use strict';

    function init() {
        const form = document.getElementById('wc-config-form');
        if (!form) {
            return;
        }
        const preview = document.getElementById('wc-preview');
        const colorValid = form.elements.color_valid;
        const colorWarning = form.elements.color_warning;
        const fullRow = form.elements.full_row;

        function refresh() {
            preview.classList.toggle('warranty-fullrow', fullRow.checked);
            for (const [state, input] of [['valid', colorValid], ['warning', colorWarning]]) {
                const tr = preview.querySelector('tr[data-warranty="' + state + '"]');
                tr.style.setProperty('--warranty-color', input.value);
                tr.querySelector('.warranty-badge').style.setProperty('--warranty-color', input.value);
                form.querySelector('[data-hex-for="' + input.name + '"]').textContent = input.value;
            }
        }

        form.addEventListener('input', refresh);
        document.getElementById('wc-reset').addEventListener('click', () => {
            form.elements.days_before_expiration.value = form.dataset.defaultDays;
            colorValid.value = form.dataset.defaultValid;
            colorWarning.value = form.dataset.defaultWarning;
            fullRow.checked = form.dataset.defaultFullRow === '1';
            for (const o of form.elements['excluded_states[]'].options) {
                o.selected = false;
            }
            refresh();
        });
        refresh();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
