/* Warranty Color : page de configuration (aperçu en direct, valeurs par défaut).
   Les champs couleur (Spectrum) et la liste des états (Select2) de GLPI signalent leurs changements via jQuery :
   on écoute donc avec jQuery quand il est disponible. */
(function () {
    'use strict';

    const HEX = /^#[0-9a-f]{6}$/i;

    function init() {
        const form = document.getElementById('wc-config-form');
        if (!form) {
            return;
        }
        const $ = window.jQuery;
        const preview = document.getElementById('wc-preview');
        const live = document.getElementById('wc-live');
        const days = document.getElementById('wc-days');
        const colorValid = document.getElementById('wc-valid');
        const colorWarning = document.getElementById('wc-warning');
        const fullRow = document.getElementById('wc-full-row');
        const states = form.querySelector('select[name="excluded_states[]"]');

        function refresh() {
            preview.classList.toggle('warranty-fullrow', fullRow.checked);
            for (const [state, input] of [['valid', colorValid], ['warning', colorWarning]]) {
                if (!HEX.test(input.value)) {
                    continue;           // saisie en cours : on garde la dernière couleur valide
                }
                const tr = preview.querySelector('tr[data-warranty="' + state + '"]');
                tr.style.setProperty('--warranty-color', input.value);
                tr.querySelector('.warranty-badge').style.setProperty('--warranty-color', input.value);
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

        function onChange() {
            live.textContent = '';
            refresh();
        }
        if ($) {
            $(form).on('input change', onChange);
        } else {
            form.addEventListener('input', onChange);
            form.addEventListener('change', onChange);
        }

        function setColor(input, value) {
            input.value = value;
            if ($ && $.fn.spectrum) {
                $(input).spectrum('set', value);
            }
        }

        document.getElementById('wc-reset').addEventListener('click', () => {
            days.value = form.dataset.defaultDays;
            setColor(colorValid, form.dataset.defaultValid);
            setColor(colorWarning, form.dataset.defaultWarning);
            fullRow.checked = form.dataset.defaultFullRow === '1';
            if ($) {
                $(states).val([]).trigger('change');
            } else {
                for (const option of states.options) {
                    option.selected = false;
                }
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
