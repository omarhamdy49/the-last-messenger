/**
 * Interactive genealogy tree.
 *
 * LOADED LAZILY. This module (and the d3 pieces it pulls in) is code-split into
 * its own chunk by the `import()` in resources/js/app.js, and fetched only when
 * a visitor actually opens the interactive canvas. It never rides along in the
 * site-wide bundle, so a reader who only scrolls the lineage spine — which is
 * the default at every width — never downloads d3 at all.
 *
 * WHAT IT DRAWS. One rooted hierarchy: the oldest recorded forefather at the
 * top, the nasab chain running down through عبد المطلب — where the uncles and
 * their children branch off — to the focus person ﷺ and his descendants.
 * Marriages hang off their person as a chip on the card, because a spouse is
 * part of how a person reads in a family tree but is not an edge of descent.
 *
 * CARDS ARE REAL DOM, NOT <foreignObject>.
 * They used to be HTML inside SVG <foreignObject>, which renders correctly in
 * Blink and is unreliable in WebKit: a <foreignObject> under a transformed <g>
 * is a long-standing iOS Safari rendering failure, so on an iPhone the whole
 * tree came up blank while Chrome's device emulation — still Blink — never
 * reproduced it. That is why the page "worked when you resized Chrome" and did
 * not work on the phone. Cards are now absolutely-positioned <button> elements
 * in a transformed <div>; only the connector paths are SVG. As a bonus they are
 * genuinely focusable and readable by a screen reader, which foreignObject
 * buttons were not.
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

/**
 * Card and slot geometry per breakpoint. A phone is not a small desktop: at
 * 375px the desktop slot showed one and a half cards out of eighty people, so
 * the compact profile shrinks the card AND tightens the ranks rather than
 * relying on the user to zoom out to an unreadable scale.
 */
const SIZES = {
    compact: { cardW: 148, cardH: 62, focusW: 176, focusH: 78, nodeX: 164, nodeY: 124 },
    regular: { cardW: 178, cardH: 74, focusW: 216, focusH: 92, nodeX: 200, nodeY: 168 },
};

const COMPACT_BELOW = 640;

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

    const size = () => container.getBoundingClientRect();

    let metrics = SIZES.regular;
    const pickMetrics = () => {
        const next = size().width < COMPACT_BELOW ? SIZES.compact : SIZES.regular;
        const changed = next !== metrics;
        metrics = next;
        return changed;
    };
    pickMetrics();

    const nodeW = (d) => (d.data.kind === 'focus' ? metrics.focusW : metrics.cardW);
    const nodeH = (d) => (d.data.kind === 'focus' ? metrics.focusH : metrics.cardH);

    // ── Scaffold ──────────────────────────────────────────────────────────
    // One transformed scene holds both layers, so the edges and the cards can
    // never drift out of register: they share a single transform rather than
    // each applying their own.
    const root_el = select(container).append('div').attr('class', 'gt-root');

    const scene = root_el.append('div').attr('class', 'gt-scene');

    // Sized and positioned to the scene's bounding box by fitEdgeLayer() on
    // every render.
    //
    // It used to be width=0 height=0 with `overflow: visible`, on the theory
    // that the paths would simply spill out of a degenerate box. They did not:
    // SVG says a width or height of zero *disables rendering of the element
    // and all its children*, so the connector lines were never painted at all
    // and the tree was a field of unlinked cards. `overflow` cannot rescue an
    // element that is not rendered.
    const edgeLayer = scene.append('svg')
        .attr('class', 'gt-edges')
        .attr('aria-hidden', 'true');

    const nodeLayer = scene.append('div')
        .attr('class', 'gt-nodes')
        .attr('role', 'tree')
        .attr('dir', rtl ? 'rtl' : 'ltr')
        .attr('aria-label', labels.canvasLabel ?? 'Family tree');

    const zoomBehavior = zoom()
        .scaleExtent([0.2, 2.5])
        .on('zoom', (event) => {
            const { x, y, k } = event.transform;
            scene.style('transform', `translate(${x}px, ${y}px) scale(${k})`);
        });

    root_el.call(zoomBehavior).on('dblclick.zoom', null);

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

    // The uncles themselves start parked too.
    //
    // عبد المطلب has fourteen other children besides عبد الله, and drawing all
    // fifteen on one rank pushed the line of descent off to the side of a rank
    // the reader has to scan to find it. So on any ancestor that has BOTH the
    // next link of the chain and collateral siblings, the collaterals are held
    // back and the chain is all that is drawn — the ancestor carries a "+14"
    // and the branch opens on request.
    //
    // Kept separate from `_children` because this is a partial hide: the node
    // still has visible children (the chain), which the plain swap cannot
    // express. An uncle whose children are ALL collateral is untouched here and
    // keeps the ordinary collapse above.
    root.each((node) => {
        if (! node.children) return;

        const collaterals = node.children.filter((c) => c.data.kind === 'collateral');

        if (collaterals.length === 0 || collaterals.length === node.children.length) return;

        node._collaterals = collaterals;
        node.children = node.children.filter((c) => c.data.kind !== 'collateral');
    });

    const layout = tree().separation((a, b) => (a.parent === b.parent ? 1 : 1.18));

    const dir = rtl ? -1 : 1;
    const x = (node) => node.x * dir;

    /**
     * The node the layout is drawn from — not necessarily the data root.
     *
     * The ascent is nineteen ranks of single-file chain above عبد المطلب, and
     * on the canvas that is a long grey column you scroll past to reach the
     * subject of the page. Collapsing it is not a `children` operation: those
     * people are the node's ANCESTORS, so there is nothing to park. Instead the
     * tree is re-rooted at the chosen forefather and everything above him stops
     * being laid out at all. The chip on his card puts it back.
     */
    let viewRoot = root;

    /** How many forefathers sit above `node` and are currently not drawn. */
    const hiddenAncestors = (node) => node.ancestors().length - 1;

    const focusNode = () => viewRoot.descendants().find((n) => n.id === focusId) ?? viewRoot;

    // The server sends the monogram computed from the bare name; the local
    // derivation is only a fallback for records that predate it.
    const initial = (d) => {
        if (d.monogram) return d.monogram;
        const trimmed = (d.name || '').replace(/^[^\p{L}\p{N}]+/u, '');
        return (trimmed || d.name || '۞').charAt(0);
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
        // The relation the server worked out ("الجدّ ١٢", "عمّ") — the tree used
        // to show the kunya here, which repeated the name for most people and
        // said nothing about how they connect to the focus person.
        const sub = esc(d.data.relationLabel || d.data.title || '');
        const crown = d.data.isProphet
            ? `<span class="gt-crown"><svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M5 16L3 6l5.5 4L12 4l3.5 6L21 6l-2 10H5zm14 3H5v-2h14v2z"/></svg></span>`
            : '';

        return `${crown}
            <span class="gt-ava${isFocus ? ' gt-ava--focus' : ''}" aria-hidden="true">${esc(initial(d.data))}</span>
            <span class="gt-meta">
                <span class="gt-name">${name}</span>
                ${sub ? `<span class="gt-sub">${sub}</span>` : ''}
                ${spouseLine(d)}
            </span>`;
    };

    // A node is "closed" when it is holding something back — either its whole
    // subtree (`_children`) or just its collateral siblings (`_collaterals`).
    const hiddenOf = (d) => d._children || d._collaterals || null;

    const toggleLabel = (d) => (hiddenOf(d)
        ? (labels.expand ?? 'Expand')
        : (labels.collapse ?? 'Collapse'));

    const toggleText = (d) => {
        const hidden = hiddenOf(d);
        return hidden ? `+${hidden.length}` : '−';
    };

    // The up-chip reads "+19" while the ascent is folded away at this node,
    // and "−" while it is on screen and this card could fold it.
    const ancestorsFolded = (d) => d === viewRoot && d.parent;
    const ancestorText = (d) => (ancestorsFolded(d) ? `+${hiddenAncestors(d)}` : '−');
    const ancestorLabel = (d) => (ancestorsFolded(d)
        ? (labels.showAncestors ?? 'Show forefathers')
        : (labels.hideAncestors ?? 'Hide forefathers'));

    // ── Render (canonical collapsible-tree data join) ─────────────────────
    const link = linkVertical().x((d) => d[0]).y((d) => d[1]);

    const diagonal = (s, t) => link({
        source: [x(s), s.y + nodeH(s) / 2],
        target: [x(t), t.y - nodeH(t) / 2],
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
    /**
     * Give the edge layer a real box covering the whole scene.
     *
     * `viewBox` is set to the same rect as the width/height, so one SVG user
     * unit stays one pixel and the paths can go on being drawn in raw scene
     * coordinates — including the negative ones to the left of the root. The
     * padding leaves room for the Bézier control points to bow outside the
     * node bounds without being clipped.
     */
    function fitEdgeLayer() {
        const nodes = viewRoot.descendants();
        const pad = Math.max(metrics.nodeX, metrics.nodeY);

        const minX = Math.min(...nodes.map((n) => x(n))) - pad;
        const maxX = Math.max(...nodes.map((n) => x(n))) + pad;
        const minY = Math.min(...nodes.map((n) => n.y)) - pad;
        const maxY = Math.max(...nodes.map((n) => n.y)) + pad;

        const w = Math.max(1, maxX - minX);
        const h = Math.max(1, maxY - minY);

        edgeLayer
            .attr('width', w)
            .attr('height', h)
            .attr('viewBox', `${minX} ${minY} ${w} ${h}`)
            .style('left', `${minX}px`)
            .style('top', `${minY}px`);
    }

    function render(source, instant = false) {
        layout.nodeSize([metrics.nodeX, metrics.nodeY]);
        layout(viewRoot);

        // Before the paths are drawn: a collapsed branch shrinks the scene and
        // an expanded one grows it, so the box is recomputed every render.
        fitEdgeLayer();

        // selection.style/attr work identically on selections and transitions,
        // so one accessor swaps the whole render between the two modes.
        const staged = (sel) => (instant || dur === 0 ? sel : sel.transition().duration(dur));

        const nodes = viewRoot.descendants();
        const links = viewRoot.links();

        // ── Nodes ──
        const node = nodeLayer.selectAll('div.gt-node').data(nodes, (d) => d.id);

        // Position rides on `transform`, not left/top: the scene holds up to a
        // few hundred cards and only transform/opacity stay off the layout
        // path.
        //
        // PIXELS ONLY — no percentage. This used to end with
        // `translate(-50%, -50%)` to centre the card on its layout point, and
        // that one clause broke every node animation: d3's transform
        // interpolator parses the string by constructing a DOMMatrix, and
        // DOMMatrix rejects percentages ("Values must be resolvable at parse
        // time") because they depend on the element's own size. Every node
        // tween threw the moment it started, which killed the exit transition
        // too — so collapsing a branch removed its connectors while leaving
        // all its cards sitting in the DOM at full opacity. The links were
        // unaffected because they animate `d` and `opacity`, never a
        // transform. Centring is done with negative margins instead.
        const place = (node_) => `translate(${x(node_)}px, ${node_.y}px)`;

        const nodeEnter = node.enter()
            .append('div')
            .attr('class', 'gt-node')
            .style('transform', place(source))
            .style('opacity', 0);

        nodeEnter.each(function (d) {
            const g = select(this);

            g.append('button')
                .attr('type', 'button')
                .attr('class', `gt-card gt-card--${d.data.kind || 'descendant'}${d.data.isProphet ? ' gt-card--prophet' : ''}`)
                .attr('title', d.data.name || '')
                .html(cardHtml(d))
                .on('click', (event) => {
                    event.stopPropagation();
                    onSelect(d.data);
                })
                // Tabbing to an off-screen card would otherwise move focus to
                // something the camera is not looking at.
                .on('focus', () => centerOn(d));

            // Ancestors are folded from the card of the forefather you want to
            // stop at, so the chip sits ABOVE the card — the direction the
            // people it hides actually lie in.
            if (d.data.kind === 'ancestor' && (d.parent || d === viewRoot)) {
                g.append('button')
                    .attr('type', 'button')
                    .attr('class', 'gt-toggle gt-toggle--up')
                    .attr('aria-label', ancestorLabel(d))
                    .text(ancestorText(d))
                    .on('click', (event) => {
                        event.stopPropagation();
                        toggleAncestors(d);
                    });
            }

            if (d.children || d._children || d._collaterals) {
                g.append('button')
                    .attr('type', 'button')
                    .attr('class', 'gt-toggle')
                    .attr('aria-expanded', String(! hiddenOf(d)))
                    .attr('aria-label', toggleLabel(d))
                    .text(toggleText(d))
                    .on('click', (event) => {
                        event.stopPropagation();
                        toggle(d);
                    });
            }
        });

        const merged = node.merge(nodeEnter);

        // Geometry that must not animate: the box a card occupies changes only
        // when the breakpoint does, and interpolating it would smear the text.
        // (The toggle chip is placed off this box in CSS, at `top: 100%`.)
        merged
            .style('width', (d) => `${nodeW(d)}px`)
            .style('height', (d) => `${nodeH(d)}px`)
            // Half the box back and up: this is what the percentage translate
            // used to do, in a form the transform interpolator can survive.
            .style('margin-left', (d) => `${-nodeW(d) / 2}px`)
            .style('margin-top', (d) => `${-nodeH(d) / 2}px`);

        staged(merged)
            .style('transform', (d) => place(d))
            .style('opacity', 1);

        // Collapse counters and their announced state redraw as branches move.
        merged.select('.gt-toggle:not(.gt-toggle--up)')
            .attr('aria-expanded', (d) => String(! hiddenOf(d)))
            .attr('aria-label', (d) => toggleLabel(d))
            .text((d) => toggleText(d));

        merged.select('.gt-toggle--up')
            .attr('aria-label', (d) => ancestorLabel(d))
            .text((d) => ancestorText(d));

        // Removal is bound to the transition's own end/interrupt events rather
        // than to `transition.remove()`.
        //
        // `transition.remove()` only fires if that exact transition runs to
        // completion. Anything that interrupts it — a second toggle, a resize
        // re-render, a transition d3 replaces on the same element — cancels the
        // removal silently, and the card is orphaned in the DOM at full opacity
        // in its old position. That is what made collapsing look inert: the
        // connectors disappeared (the link join removes cleanly) while every
        // card stayed exactly where it was.
        const exiting = node.exit();

        if (instant || dur === 0) {
            exiting.remove();
        } else {
            exiting.transition()
                .duration(dur)
                .style('transform', place(source))
                .style('opacity', 0)
                .on('end interrupt', function () { this.remove(); });
        }

        // ── Links ──
        const edge = edgeLayer.selectAll('path.gt-link').data(links, (d) => d.target.id);

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

    /** Park a node's children without discarding them. */
    const fold = (node) => {
        if (node.children) {
            node._children = node.children;
            node.children = null;
        }
    };

    /**
     * One generation per click.
     *
     * The canonical d3 collapse parks the whole subtree, which on this data
     * meant a single − on محمد ﷺ removed twenty-six people across three
     * generations at once — and the matching + dumped all twenty-six back.
     * Anything beneath a collapsed node has to hide, that much is unavoidable
     * in a tree layout; what is avoidable is the explosion on the way back.
     *
     * So both directions normalise the level below to "folded": collapsing
     * folds each child's own subtree first, and expanding re-folds the children
     * it reveals. Every click then moves the view exactly one generation, and
     * "+N" on a card always means "N people, one rank down".
     *
     * expandAll() deliberately bypasses this — asking for everything is a
     * different request from stepping through.
     */
    function toggleAncestors(d) {
        viewRoot = (d === viewRoot) ? root : d;
        render(d, true);
        centerOn(focusNode(), null, false);
    }

    function toggle(d) {
        // An ancestor holding back its uncles: reveal or re-hide just those,
        // never the chain link beside them.
        if (d._collaterals) {
            d._shownCollaterals = d._collaterals;
            d.children = (d.children || []).concat(d._collaterals);
            d._collaterals = null;
            d._shownCollaterals.forEach(fold);
        } else if (d._shownCollaterals) {
            const hiding = d._shownCollaterals;
            d._collaterals = hiding;
            d._shownCollaterals = null;
            d.children = (d.children || []).filter((c) => ! hiding.includes(c));
        } else if (d.children) {
            d.children.forEach(fold);
            fold(d);
        } else {
            d.children = d._children;
            d._children = null;
            (d.children || []).forEach(fold);
        }

        render(d);
    }

    // ── Camera ────────────────────────────────────────────────────────────
    /**
     * Frame a person — and, where there is one, their parent with them.
     *
     * The opening shot used to put the focus person alone at 42% height, which
     * left عبد الله ﷺ's father half off the top edge: the one relationship a
     * reader opens a nasab to see was the one cropped. Aiming at the midpoint
     * of the pair centres both.
     */
    function centerOn(node, scale = null, animate = true) {
        const { width, height } = size();
        scale ??= Math.max(0.5, Math.min(0.95, width / 900));

        const pair = (node !== viewRoot && node.parent) ? node.parent : node;
        const cx = (x(node) + x(pair)) / 2;
        const cy = (node.y + pair.y) / 2;

        const t = zoomIdentity
            .translate(width / 2 - cx * scale, height / 2 - cy * scale)
            .scale(scale);

        (animate && dur > 0 ? root_el.transition().duration(500) : root_el).call(zoomBehavior.transform, t);
    }

    /**
     * Frame the tree without zooming past the point of legibility.
     *
     * A nasab is a tall, narrow shape: twenty-two ranks deep and rarely more
     * than a few cards wide. Fitting both axes literally solved for a scale
     * near 0.2, which turned eighty cards into an unreadable grey column and
     * answered no question at all. So the width is fitted and the scale is
     * floored; when the chain is too tall to show at that floor the camera
     * keeps the focus person in frame and the reader pans, which is the same
     * bargain the spine makes.
     */
    const FIT_FLOOR = 0.45;

    function fit(animate = true) {
        const nodes = viewRoot.descendants();
        const pad = 48;
        const minX = Math.min(...nodes.map((n) => x(n) - nodeW(n) / 2)) - pad;
        const maxX = Math.max(...nodes.map((n) => x(n) + nodeW(n) / 2)) + pad;
        const minY = Math.min(...nodes.map((n) => n.y - nodeH(n) / 2)) - pad;
        const maxY = Math.max(...nodes.map((n) => n.y + nodeH(n) / 2)) + pad;
        const { width, height } = size();

        const exact = Math.min(width / (maxX - minX), height / (maxY - minY));
        const scale = Math.min(2, Math.max(FIT_FLOOR, exact));

        // Whole tree fits at a readable scale → centre the tree. It does not →
        // centre the person the page is about.
        const centreY = exact >= FIT_FLOOR ? (minY + maxY) / 2 : focusNode().y;
        const centreX = exact >= FIT_FLOOR ? (minX + maxX) / 2 : x(focusNode());

        const t = zoomIdentity
            .translate(width / 2 - centreX * scale, height / 2 - centreY * scale)
            .scale(scale);

        (animate && dur > 0 ? root_el.transition().duration(500) : root_el).call(zoomBehavior.transform, t);
    }

    // A rotated phone, an opened fullscreen sheet or a dragged desktop window
    // all change which geometry profile is right. Re-lay-out only when the
    // profile actually flips; a plain resize just needs the camera re-aimed.
    let resizeTimer = null;
    const observer = new ResizeObserver(() => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => {
            if (size().width === 0) return; // Hidden container: nothing to aim at.
            if (pickMetrics()) render(root, true);
            centerOn(focusNode(), null, false);
        }, 150);
    });
    observer.observe(container);

    // First paint: instant (no timers — see render), camera on the focus
    // person — the Prophet ﷺ, not the top of a 21-generation chain.
    render(viewRoot, true);
    centerOn(focusNode(), null, false);

    return {
        zoomIn: () => (dur > 0 ? root_el.transition().duration(200) : root_el).call(zoomBehavior.scaleBy, 1.35),
        zoomOut: () => (dur > 0 ? root_el.transition().duration(200) : root_el).call(zoomBehavior.scaleBy, 1 / 1.35),
        fit: () => fit(),
        centerFocus: () => centerOn(focusNode()),
        expandAll: () => {
            viewRoot.each((n) => {
                if (n._collaterals) {
                    n._shownCollaterals = n._collaterals;
                    n.children = (n.children || []).concat(n._collaterals);
                    n._collaterals = null;
                }
                if (n._children) {
                    n.children = n._children;
                    n._children = null;
                }
            });
            render(viewRoot);
            fit();
        },
        collapseAll: () => {
            viewRoot.each((n) => {
                if (n.data.kind === 'collateral' && n.children) {
                    n._children = n.children;
                    n.children = null;
                }
                if (n._shownCollaterals) {
                    const hiding = n._shownCollaterals;
                    n._collaterals = hiding;
                    n._shownCollaterals = null;
                    n.children = (n.children || []).filter((c) => ! hiding.includes(c));
                }
            });
            render(viewRoot);
            centerOn(focusNode());
        },
        // Focus a person by id — the page's search and the spine both hand off
        // to the canvas this way.
        focusPerson: (id) => {
            const node = root.descendants().find((n) => n.id === id);
            if (node) centerOn(node, 0.95);
            return !!node;
        },
        destroy: () => {
            observer.disconnect();
            clearTimeout(resizeTimer);
            root_el.remove();
        },
    };
}

// A plain module export. The window-global side effect that used to live here
// existed only because this file was a Vite *entry*, whose export signature the
// production build does not preserve. It is a normal dynamic-import target now,
// so the export is enough.
export { mountGenealogyTree };
