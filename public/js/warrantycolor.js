/* Warranty Color : repère l'état de garantie dans les listes d'équipements (pastille + teinte de ligne + légende). */
(function () {
    'use strict';

    // Adresse du plugin déduite du script lui-même (marketplace, sous-répertoire…) ; à lire avant tout « await ».
    const SCRIPT = document.currentScript;
    const BASE = SCRIPT ? SCRIPT.src.replace(/\/js\/[^/]*$/, '') : '/plugins/warrantycolor';
    const STATUS_URL = BASE + '/front/status.php';

    const CHUNK = 40;               // adresses par requête
    const DEBOUNCE_MS = 150;
    const FORM_LINK = /\.form\.php\?(?:[^"#]*&)?id=\d+/;
    const ICONS = {valid: 'ti-shield-check', warning: 'ti-alert-triangle', expired: 'ti-shield-off'};

    const cache = new Map();        // adresse -> info | null (déjà demandée)
    let meta = null;                // {config, labels, colors} renvoyé par le serveur
    let running = false;
    let dirty = false;
    let timer = null;
    let applying = false;

    const rowLink = tr => {
        for (const a of tr.querySelectorAll('a[href]')) {
            if (FORM_LINK.test(a.getAttribute('href'))) {
                return a;
            }
        }
        return null;
    };

    function badge(state, color, text) {
        const b = document.createElement('span');
        b.className = 'warranty-badge';
        b.dataset.state = state;
        if (color) {
            b.style.setProperty('--warranty-color', color);
        }
        b.setAttribute('role', 'img');
        b.setAttribute('aria-label', text);
        b.setAttribute('tabindex', '0');            // info-bulle accessible au clavier
        b.dataset.tip = text;                       // info-bulle du plugin (couleur suivant l'état), pas celle de GLPI
        const i = document.createElement('i');
        i.className = 'ti ' + ICONS[state];
        i.setAttribute('aria-hidden', 'true');
        b.appendChild(i);
        return b;
    }

    async function fetchChunk(hrefs) {
        const qs = hrefs.map(h => 'href[]=' + encodeURIComponent(h)).join('&');
        const res = await fetch(STATUS_URL + '?' + qs, {credentials: 'same-origin'});
        if (!res.ok) {
            throw new Error('status ' + res.status);
        }
        const data = await res.json();
        meta = {config: data.config, labels: data.labels, colors: data.colors};
        for (const h of hrefs) {
            cache.set(h, data.items[h] || null);
        }
    }

    function applyRow(tr, a, info) {
        tr.dataset.warranty = info.state;
        if (info.color) {
            tr.style.setProperty('--warranty-color', info.color);
        }
        tr.querySelector('.warranty-badge')?.remove();
        a.closest('td, th')?.appendChild(badge(info.state, info.color, info.label));
    }

    function addLegend(table) {
        if (!meta || table.dataset.warrantyLegend) {
            return;
        }
        table.dataset.warrantyLegend = '1';
        const legend = document.createElement('div');
        legend.className = 'warranty-legend';
        legend.setAttribute('aria-label', meta.labels.title);
        for (const state of ['valid', 'warning', 'expired']) {
            const item = document.createElement('span');
            item.appendChild(badge(state, meta.colors[state], meta.labels[state]));
            item.appendChild(document.createTextNode(meta.labels[state]));
            legend.appendChild(item);
        }
        (table.closest('.table-responsive') || table).before(legend);
    }

    async function colorize() {
        if (running) {
            dirty = true;
            return;
        }
        running = true;
        try {
            for (const table of document.querySelectorAll('table.search-results')) {
                const todo = [];
                for (const tr of table.querySelectorAll('tbody tr:not([data-warranty-done])')) {
                    const a = rowLink(tr);
                    if (a) {
                        todo.push({tr, a, href: a.getAttribute('href')});
                    }
                }
                const missing = [...new Set(todo.map(t => t.href).filter(h => !cache.has(h)))];
                const chunks = [];
                for (let i = 0; i < missing.length; i += CHUNK) {
                    chunks.push(missing.slice(i, i + CHUNK));
                }
                await Promise.all(chunks.map(fetchChunk));

                applying = true;
                let any = false;
                for (const {tr, a, href} of todo) {
                    const info = cache.get(href);
                    if (info) {
                        applyRow(tr, a, info);
                        any = true;
                    }
                    tr.setAttribute('data-warranty-done', '1');     // posé après succès : un échec sera retenté
                }
                if (any && meta) {
                    table.classList.toggle('warranty-fullrow', !!meta.config.full_row);
                    addLegend(table);
                }
                applying = false;
            }

            // Fiches ticket / problème / changement : pastille à côté de chaque équipement associé.
            const links = [...document.querySelectorAll('.accordion-items a[href]:not([data-warranty-done])')]
                .filter(a => FORM_LINK.test(a.getAttribute('href')));
            if (links.length) {
                const missing = [...new Set(links.map(a => a.getAttribute('href')).filter(h => !cache.has(h)))];
                const chunks = [];
                for (let i = 0; i < missing.length; i += CHUNK) {
                    chunks.push(missing.slice(i, i + CHUNK));
                }
                await Promise.all(chunks.map(fetchChunk));

                applying = true;
                for (const a of links) {
                    const info = cache.get(a.getAttribute('href'));
                    if (a.nextElementSibling?.classList.contains('warranty-badge')) {
                        a.nextElementSibling.remove();
                    }
                    if (info) {
                        a.after(badge(info.state, info.color, info.label));
                    }
                    a.setAttribute('data-warranty-done', '1');
                }
                applying = false;
            }
        } catch (e) {
            console.warn('Warranty Color :', e);
        } finally {
            applying = false;
            running = false;
            if (dirty) {
                dirty = false;
                schedule();
            }
        }
    }

    function schedule() {
        clearTimeout(timer);
        timer = setTimeout(colorize, DEBOUNCE_MS);
    }

    function start() {
        schedule();
        const root = document.querySelector('main, .page-body') || document.body;
        new MutationObserver(() => {
            if (!applying) {
                schedule();
            }
        }).observe(root, {childList: true, subtree: true});
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }
})();
