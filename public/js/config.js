/* Warranty Color : page de configuration (onglets, aperçu en direct, valeurs par défaut, mails automatiques).
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

        // Pastille « modifié » sur l'onglet dont un champ a changé.
        function markDirty(target) {
            const pane = target.closest ? target.closest('.tab-pane') : null;
            if (!pane) {
                return;
            }
            const tab = document.querySelector('[data-bs-target="#' + pane.id + '"]');
            tab?.querySelector('.wc-dirty')?.classList.remove('d-none');
            tab?.querySelector('.wc-dirty-label')?.classList.remove('d-none');
        }

        // Mode affiché sous la case « simulation » (SIMULATION / ENVOI RÉEL) : suit la case avant l'enregistrement.
        const simulation = document.getElementById('wc-notify-simulation');
        const badge = document.getElementById('wc-mode-badge');
        function refreshMode() {
            if (!simulation || !badge) {
                return;
            }
            badge.textContent = simulation.checked ? form.dataset.modeSimulation : form.dataset.modeReal;
            badge.classList.toggle('bg-blue', simulation.checked);
            badge.classList.toggle('bg-red', !simulation.checked);
        }

        function onChange(event) {
            live.textContent = '';
            refresh();
            refreshMode();
            if (event && event.target) {
                markDirty(event.target);
            }
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
            markDirty(days);
            live.textContent = form.dataset.resetMessage;       // annoncé (aria-live) : rien n'est enregistré tant qu'on n'a pas validé
        });

        // Texte du mail : retour au texte par défaut (traduit) du plugin.
        document.getElementById('wc-text-reset')?.addEventListener('click', () => {
            document.getElementById('wc-notify-subject').value = form.dataset.defaultSubject;
            document.getElementById('wc-notify-body').value = form.dataset.defaultBody;
            markDirty(document.getElementById('wc-notify-body'));
            live.textContent = form.dataset.textResetMessage;
        });

        // Marquage « déjà notifiés » : confirmation explicite (les modifications du formulaire ne sont pas enregistrées).
        document.getElementById('wc-baseline')?.addEventListener('click', (event) => {
            if (!window.confirm(form.dataset.baselineConfirm)) {
                event.preventDefault();
            }
        });

        // Onglets : l'onglet actif est mémorisé dans l'adresse (#mails) et renvoyé à l'enregistrement pour y revenir.
        const tabInput = document.getElementById('wc-tab-input');
        for (const button of document.querySelectorAll('[data-wc-tab]')) {
            button.addEventListener('shown.bs.tab', () => {
                tabInput.value = button.dataset.wcTab;
                history.replaceState(null, '', '#' + button.dataset.wcTab);
            });
        }
        const wanted = location.hash.replace('#', '');
        const wantedButton = wanted ? document.querySelector('[data-wc-tab="' + wanted + '"]') : null;
        if (wantedButton && !wantedButton.classList.contains('active')) {
            if (window.bootstrap && window.bootstrap.Tab) {
                window.bootstrap.Tab.getOrCreateInstance(wantedButton).show();
            } else {
                wantedButton.click();
            }
        }

        refresh();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
