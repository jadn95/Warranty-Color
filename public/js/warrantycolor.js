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

    const CHECKBOX = /^item\[([^\]]+)\]\[(\d+)\]$/;      // case de sélection native des listes GLPI : item[Type][id]

    const rowLink = tr => {
        for (const a of tr.querySelectorAll('a[href]')) {
            if (FORM_LINK.test(a.getAttribute('href'))) {
                return a;
            }
        }
        return null;
    };

    /**
     * Identifie une ligne de liste : case de sélection native de GLPI (Type + id, indépendante de l'adresse des fiches)
     * en priorité, sinon lien vers la fiche. Renvoie la clé d'interrogation et la cellule qui reçoit la pastille.
     */
    function rowInfo(tr) {
        const box = tr.querySelector('input[type="checkbox"][name^="item["]');
        const m = box && CHECKBOX.exec(box.getAttribute('name'));
        const link = rowLink(tr);
        const key = m ? m[1] + ':' + m[2] : (link ? link.getAttribute('href') : null);
        const cell = tr.querySelector('td[data-searchopt-content-id="1"]') || (link && link.closest('td, th'));
        return key && cell ? {key, cell} : null;
    }

    function badge(state, color, text) {
        const b = document.createElement('span');
        b.className = 'warranty-badge';
        b.dataset.state = state;
        if (color) {
            b.style.setProperty('--warranty-color', color);
        }
        b.setAttribute('role', 'img');
        b.setAttribute('aria-label', text);
        b.dataset.tip = text;                       // info-bulle du plugin (couleur suivant l'état), pas celle de GLPI
        const i = document.createElement('i');
        i.className = 'ti ' + ICONS[state];
        i.setAttribute('aria-hidden', 'true');
        b.appendChild(i);
        return b;
    }

    /** Charge (en parallèle, par lots) les états des adresses pas encore connues. */
    async function load(hrefs) {
        const missing = [...new Set(hrefs.filter(h => !cache.has(h)))];
        const chunks = [];
        for (let i = 0; i < missing.length; i += CHUNK) {
            chunks.push(missing.slice(i, i + CHUNK));
        }
        await Promise.all(chunks.map(fetchChunk));
    }

    async function fetchChunk(hrefs) {
        const qs = hrefs.map(h => (/^[\w\\]+:\d+$/.test(h) ? 'item[]=' : 'href[]=') + encodeURIComponent(h)).join('&');
        const res = await fetch(STATUS_URL + '?' + qs, {credentials: 'same-origin'});
        if (!res.ok) {
            throw new Error('status ' + res.status);
        }
        const data = await res.json();
        meta = {config: data.config, labels: data.labels, colors: data.colors, types: data.types || []};
        for (const h of hrefs) {
            cache.set(h, data.items[h] || null);
        }
    }

    function applyRow(tr, cell, info) {
        tr.dataset.warranty = info.state;
        if (info.color) {
            tr.style.setProperty('--warranty-color', info.color);
        }
        tr.querySelector('.warranty-badge')?.remove();
        cell.appendChild(badge(info.state, info.color, info.label));
    }

    function addLegend(table, listType) {
        if (!meta || table.dataset.warrantyLegend) {
            return;
        }
        table.dataset.warrantyLegend = '1';
        const legend = document.createElement('div');
        legend.className = 'warranty-legend';
        legend.setAttribute('aria-label', meta.labels.title);
        const title = document.createElement('span');
        title.className = 'warranty-legend-title';
        title.textContent = meta.labels.title + ' :';
        legend.appendChild(title);
        // type d'équipement de la liste : celui de la page (formulaire de recherche GLPI), sinon celui d'une ligne signalée
        let type = listType;
        for (const tr of type ? [] : table.querySelectorAll('tbody tr')) {
            const r = rowInfo(tr);
            type = r && cache.get(r.key)?.itemtype;
            if (type) {
                break;
            }
        }
        for (const state of ['valid', 'warning', 'expired']) {
            const item = document.createElement(type ? 'a' : 'span');
            if (type) {          // clic : liste de tous les équipements du type dans cet état (toutes les pages)
                item.href = BASE + '/front/list.php?itemtype=' + encodeURIComponent(type) + '&state=' + state;
                item.className = 'warranty-legend-link';
                const hint = meta.labels.list.replace('%s', meta.labels[state]);
                item.dataset.tip = hint;                 // info-bulle du plugin (couleur de l'état) : un attribut title donnerait celle, bleue, de GLPI
                item.setAttribute('aria-label', hint);
            }
            const b = badge(state, meta.colors[state], meta.labels[state]);
            b.removeAttribute('tabindex');       // la légende porte déjà le texte : pas de seconde info-bulle
            b.removeAttribute('role');
            b.removeAttribute('aria-label');
            b.setAttribute('aria-hidden', 'true');
            delete b.dataset.tip;
            item.appendChild(b);
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
                    const r = rowInfo(tr);
                    if (r) {
                        todo.push({tr, ...r});
                    }
                }
                await load(todo.map(t => t.key));
                // Type de la liste affichée (attribut posé par GLPI sur le formulaire de recherche) : la légende n'attend pas une ligne signalée.
                const listType = table.closest('form[data-search-itemtype]')?.dataset.searchItemtype || null;
                if (listType && !meta) {
                    await fetchChunk([]);
                }

                applying = true;
                let any = false;
                for (const {tr, key, cell} of todo) {
                    const info = cache.get(key);
                    if (info) {
                        applyRow(tr, cell, info);
                        any = true;
                    }
                    tr.setAttribute('data-warranty-done', '1');     // posé après succès : un échec sera retenté
                }
                if (meta && (any || (listType && meta.types.includes(listType)))) {
                    table.classList.toggle('warranty-fullrow', !!meta.config.full_row);
                    addLegend(table, listType && meta.types.includes(listType) ? listType : null);
                }
                applying = false;
            }

            // Fiches ticket / problème / changement : pastille à côté de chaque équipement associé.
            const links = [...document.querySelectorAll('.accordion-items a[href]:not([data-warranty-done])')]
                .filter(a => FORM_LINK.test(a.getAttribute('href')));
            if (links.length) {
                await load(links.map(a => a.getAttribute('href')));

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

    // Info-bulle flottante partagée (survol ou focus clavier d'une pastille) : placée dans la fenêtre, jamais rognée.
    let tipEl = null;
    function showTip(badge) {
        hideTip();
        tipEl = document.createElement('div');
        tipEl.className = 'warranty-tooltip';
        tipEl.setAttribute('role', 'tooltip');
        tipEl.textContent = badge.dataset.tip;
        const colored = badge.matches('.warranty-badge') ? badge : badge.querySelector('.warranty-badge');       // lien de légende : couleur de sa pastille
        tipEl.style.setProperty('--wc', getComputedStyle(colored || badge).getPropertyValue('--wc'));
        document.body.appendChild(tipEl);
        const r = badge.getBoundingClientRect();
        const t = tipEl.getBoundingClientRect();
        const left = Math.max(8, Math.min(window.innerWidth - t.width - 8, r.left + r.width / 2 - t.width / 2));
        const top = r.top - t.height - 6 >= 8 ? r.top - t.height - 6 : r.bottom + 6;    // sous la pastille si pas de place au-dessus
        tipEl.style.left = left + 'px';
        tipEl.style.top = top + 'px';
    }
    function hideTip() {
        tipEl?.remove();
        tipEl = null;
    }
    const tipTarget = e => e.target.closest?.('.warranty-badge[data-tip], .warranty-legend-link[data-tip]');
    document.addEventListener('mouseover', e => { const b = tipTarget(e); if (b) showTip(b); });
    document.addEventListener('mouseout', e => { if (tipTarget(e)) hideTip(); });
    document.addEventListener('focusin', e => { const b = tipTarget(e); if (b) showTip(b); });
    document.addEventListener('focusout', hideTip);
    document.addEventListener('scroll', hideTip, true);
    document.addEventListener('keydown', e => { if (e.key === 'Escape') hideTip(); });     // WCAG 1.4.13 : info-bulle fermable au clavier

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
