/**
 * Interactive genealogy tree.
 *
 * LOADED LAZILY. This module (and the d3 pieces it pulls in) is its own Vite
 * entry, dynamically import()ed by the genealogy page the first time the tree
 * canvas approaches the viewport — it is never part of the site-wide bundle.
 *
 * WHAT IT DRAWS. One rooted hierarchy: the oldest recorded forefather at the
 * top, the nasab chain running down through عبد المطلب — where the uncles and
 * their children branch off — to the focus person ﷺ and his descendants.
 * Marriages hang off their person as a chip on the card, because a spouse is
 * part of how a person reads in a family tree but is not an edge of descent.
 *
 * Cards are HTML inside SVG <foreignObject>: real text shaping (Arabic joins,
 * RTL runs, ellipsis) is the browser's text engine's job, not something worth
 * re-implementing in SVG <text>. Styling lives in the page stylesheet, so
 * dark mode and the brand palette apply with no code here knowing about them.
 *
 * The collapsible-tree data join follows the canonical d3 pattern: hidden
 * children park in `_children`, and enter/exit transitions originate from the
 * toggled node so branches visibly grow out of and fold into their parent.
 */
import { hierarchy, tree } from 'd3-hierarchy';
import { select } from 'd3-selection';
import { zoom, zoomIdentity } from 'd3-zoom';
import { linkVertical } from 'd3-shape';
import 'd3-transition';

const CARD_W = 178;
const CARD_H = 74;
const FOCUS_W = 216;
const FOCUS_H = 92;
// Horizontal slot / vertical rank. The slot exceeds the widest card so
// neighbouring cards can never touch even before separation() spreads cousins.
const NODE_X = 200;
const NODE_Y = 168;

/**
 * @param {HTMLElement} container
 * @param {object} data      root node: {id, name, title, kind, isProphet, spouses[], children[]}
 * @param {object} options   {focusId, rtl, labels, onSelect(person)}
 * @returns tree API: {zoomIn, zoomOut, fit, centerFocus, expandAll, collapseAll, destroy}
 */
function mountGenealogyTree(container, data, options = {}) {
    const { focusId = null, rtl = false, labels = {}, onSelect = () => {} } = options;

    const reducedMotion = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches ?? false;

    // d3 transitions interpolate on requestAnimationFrame, and browsers freeze
    // rAF entirely for occluded windows and throttled tabs — leaving a toggled
    // branch stranded mid-fade at 10% opacity, which reads as broken, not slow.
    // So the frame clock is probed once at mount: if no frame lands, every
    // subsequent render writes its final state directly instead of animating.
    // (`dur`, not a constant: the probe result arrives asynchronously.)
    let dur = reducedMotion ? 0 : 380;
    if (dur > 0) {
        let frameSeen = false;
        requestAnimationFrame(() => { frameSeen = true; });
        setTimeout(() => { if (! frameSeen) dur = 0; }, 400);
    }

    // ── Scaffold ──────────────────────────────────────────────────────────
    // The SVG ignores the page's RTL direction on purpose: layout math stays
    // one-directional and the *data* is mirrored instead (see x accessor).
    const svg = select(container)
        .append('svg')
        .attr('class', 'gt-svg')
        .attr('dir', 'ltr')
        .attr('width', '100%')
        .attr('height', '100%')
        .attr('role', 'application')
        .attr('aria-label', labels.canvasLabel ?? 'Family tree');

    const canvas = svg.append('g');
    const linkLayer = canvas.append('g').attr('class', 'gt-links');
    const nodeLayer = canvas.append('g').attr('class', 'gt-nodes');

    const zoomBehavior = zoom()
        .scaleExtent([0.25, 2.5])
        .on('zoom', (event) => canvas.attr('transform', event.transform));

    svg.call(zoomBehavior).on('dblclick.zoom', null);

    // ── Hierarchy and collapse state ──────────────────────────────────────
    const root = hierarchy(data, (d) => d.children);

    root.each((node) => {
        node._children = null;
        node.id = node.data.id;
    });

    // Collateral branches (an uncle's own children) start folded: opened all
    // at once, thirty cousins bury the line of descent the page is about.
    root.each((node) => {
        if (node.data.kind === 'collateral' && node.children) {
            node._children = node.children;
            node.children = null;
        }
    });

    const layout = tree()
        .nodeSize([NODE_X, NODE_Y])
        .separation((a, b) => (a.parent === b.parent ? 1 : 1.18));

    const dir = rtl ? -1 : 1;
    const x = (node) => node.x * dir;

    const focusNode = () => root.descendants().find((n) => n.id === focusId) ?? root;

    const initial = (name) => {
        const trimmed = (name || '').replace(/^[^\p{L}\p{N}]+/u, '');
        return (trimmed || name || '۞').charAt(0);
    };

    const esc = (s) => (s || '').replace(/[&<>"]/g, (c) => (
        { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]
    ));

    // ── Card templates ────────────────────────────────────────────────────
    const spouseLine = (d) => {
        const spouses = d.data.spouses || [];
        // The focus person's marriages are drawn as spouse NODES beneath the
        // card (see GenealogyController::marriageGroups) — a ♥ summary line on
        // top of that would say the same thing twice.
        if (!spouses.length || d.data.kind === 'focus') return '';
        const first = esc(spouses[0].name);
        const more = spouses.length > 1 ? ` <b>+${spouses.length - 1}</b>` : '';
        return `<span class="gt-spouse"><svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>${first}${more}</span>`;
    };

    const cardHtml = (d) => {
        const isFocus = d.data.kind === 'focus';
        const name = esc(d.data.name);
        const sub = esc(d.data.title || '');
        const crown = d.data.isProphet
            ? `<span class="gt-crown"><svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M5 16L3 6l5.5 4L12 4l3.5 6L21 6l-2 10H5zm14 3H5v-2h14v2z"/></svg></span>`
            : '';

        return `
            <button type="button" class="gt-card gt-card--${d.data.kind || 'descendant'}${d.data.isProphet ? ' gt-card--prophet' : ''}" title="${name}">
                ${crown}
                <span class="gt-ava${isFocus ? ' gt-ava--focus' : ''}">${esc(initial(d.data.name))}</span>
                <span class="gt-meta">
                    <span class="gt-name">${name}</span>
                    ${sub ? `<span class="gt-sub">${sub}</span>` : ''}
                    ${spouseLine(d)}
                </span>
            </button>`;
    };

    const toggleHtml = (d) => {
        const kids = (d.children || d._children || []).length;
        const open = !!d.children;
        return `
            <button type="button" class="gt-toggle${open ? ' gt-toggle--open' : ''}"
                    aria-expanded="${open}" aria-label="${open ? esc(labels.collapse ?? 'Collapse') : esc(labels.expand ?? 'Expand')}">
                ${open ? '−' : `+${kids}`}
            </button>`;
    };

    // ── Render (canonical collapsible-tree data join) ─────────────────────
    const link = linkVertical().x((d) => d[0]).y((d) => d[1]);

    const diagonal = (s, t) => link({
        source: [x(s), s.y + (s.data.kind === 'focus' ? FOCUS_H : CARD_H) / 2],
        target: [x(t), t.y - (t.data.kind === 'focus' ? FOCUS_H : CARD_H) / 2],
    });

    /**
     * @param source   the node the change originated from (transitions grow
     *                 out of / fold into it)
     * @param instant  skip transitions and write final attributes directly.
     *                 The FIRST paint always renders instant: it must not
     *                 depend on animation timers, which browsers freeze in
     *                 occluded or backgrounded tabs — a tree that loads in a
     *                 background tab has to be fully drawn when the user
     *                 arrives, not stuck at 10% opacity.
     */
    function render(source, instant = false) {
        layout(root);

        // selection.attr/style work identically on selections and transitions,
        // so one accessor swaps the whole render between the two modes.
        const staged = (sel) => (instant || dur === 0 ? sel : sel.transition().duration(dur));

        const nodes = root.descendants();
        const links = root.links();

        // ── Nodes ──
        const node = nodeLayer.selectAll('g.gt-node').data(nodes, (d) => d.id);

        const nodeEnter = node.enter()
            .append('g')
            .attr('class', 'gt-node')
            .attr('transform', () => `translate(${x(source)},${source.y})`)
            .style('opacity', 0);

        nodeEnter.each(function (d) {
            const g = select(this);
            const w = d.data.kind === 'focus' ? FOCUS_W : CARD_W;
            const h = d.data.kind === 'focus' ? FOCUS_H : CARD_H;

            const fo = g.append('foreignObject')
                .attr('x', -w / 2).attr('y', -h / 2)
                .attr('width', w).attr('height', h);

            fo.append('xhtml:div')
                .attr('class', 'gt-cell')
                .attr('dir', rtl ? 'rtl' : 'ltr')
                .html(cardHtml(d))
                .on('click', (event) => {
                    event.stopPropagation();
                    onSelect(d.data);
                });

            if (d.children || d._children) {
                g.append('foreignObject')
                    .attr('class', 'gt-toggle-slot')
                    .attr('x', -28).attr('y', h / 2 - 4)
                    .attr('width', 56).attr('height', 30)
                    .append('xhtml:div')
                    .attr('class', 'gt-cell')
                    .html(toggleHtml(d))
                    .on('click', (event) => {
                        event.stopPropagation();
                        toggle(d);
                    });
            }
        });

        staged(node.merge(nodeEnter))
            .attr('transform', (d) => `translate(${x(d)},${d.y})`)
            .style('opacity', 1);

        // Collapse counters redraw as branches open and close.
        node.select('.gt-toggle-slot .gt-cell').html((d) => toggleHtml(d));

        staged(node.exit())
            .attr('transform', () => `translate(${x(source)},${source.y})`)
            .style('opacity', 0)
            .remove();

        // ── Links ──
        const edge = linkLayer.selectAll('path.gt-link').data(links, (d) => d.target.id);

        staged(
            edge.enter()
                .append('path')
                .attr('class', (d) => `gt-link gt-link--${d.target.data.kind || 'descendant'}`)
                .attr('d', () => diagonal(source, source))
                .style('opacity', 0)
                .merge(edge)
        )
            .attr('d', (d) => diagonal(d.source, d.target))
            .style('opacity', 1);

        staged(edge.exit())
            .attr('d', () => diagonal(source, source))
            .style('opacity', 0)
            .remove();
    }

    function toggle(d) {
        if (d.children) {
            d._children = d.children;
            d.children = null;
        } else {
            d.children = d._children;
            d._children = null;
        }
        render(d);
    }

    // ── Camera ────────────────────────────────────────────────────────────
    const size = () => container.getBoundingClientRect();

    function centerOn(node, scale = null, animate = true) {
        const { width, height } = size();
        scale ??= Math.max(0.45, Math.min(0.95, width / 900));
        const t = zoomIdentity
            .translate(width / 2 - x(node) * scale, height * 0.42 - node.y * scale)
            .scale(scale);
        (animate && dur > 0 ? svg.transition().duration(500) : svg).call(zoomBehavior.transform, t);
    }

    function fit(animate = true) {
        const nodes = root.descendants();
        const xs = nodes.map((n) => x(n));
        const ys = nodes.map((n) => n.y);
        const pad = 60;
        const minX = Math.min(...xs) - CARD_W / 2 - pad;
        const maxX = Math.max(...xs) + CARD_W / 2 + pad;
        const minY = Math.min(...ys) - FOCUS_H / 2 - pad;
        const maxY = Math.max(...ys) + FOCUS_H / 2 + pad;
        const { width, height } = size();
        const scale = Math.max(0.25, Math.min(2, width / (maxX - minX), height / (maxY - minY)));
        const t = zoomIdentity
            .translate(width / 2 - ((minX + maxX) / 2) * scale, height / 2 - ((minY + maxY) / 2) * scale)
            .scale(scale);
        (animate && dur > 0 ? svg.transition().duration(500) : svg).call(zoomBehavior.transform, t);
    }

    // First paint: instant (no timers — see render), camera on the focus
    // person — the Prophet ﷺ, not the top of a 21-generation chain. The
    // opening scale follows the container: a phone shows the focus card with
    // its parents and children in reach, not one card filling the screen.
    render(root, true);
    centerOn(focusNode(), null, false);

    return {
        zoomIn: () => (dur > 0 ? svg.transition().duration(200) : svg).call(zoomBehavior.scaleBy, 1.35),
        zoomOut: () => (dur > 0 ? svg.transition().duration(200) : svg).call(zoomBehavior.scaleBy, 1 / 1.35),
        fit: () => fit(),
        centerFocus: () => centerOn(focusNode()),
        expandAll: () => {
            root.each((n) => {
                if (n._children) {
                    n.children = n._children;
                    n._children = null;
                }
            });
            render(root);
            fit();
        },
        collapseAll: () => {
            root.each((n) => {
                if (n.data.kind === 'collateral' && n.children) {
                    n._children = n.children;
                    n.children = null;
                }
            });
            render(root);
            centerOn(focusNode());
        },
        destroy: () => svg.remove(),
    };
}

// Published as a window global — a side effect — rather than relying on the ES
// export alone: this file is a Vite *entry*, and rolldown's production build
// does not preserve entry export signatures, so a pure `export function` is
// tree-shaken out of the emitted chunk entirely (leaving a chunk of d3 and
// nothing else). The dynamic import()er reads the export when present (dev
// server) and falls back to this global (production build).
window.mountGenealogyTree = mountGenealogyTree;

export { mountGenealogyTree };
