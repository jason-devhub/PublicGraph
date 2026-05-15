import { Controller } from '@hotwired/stimulus';
import cytoscape from 'cytoscape';
import fcose from 'cytoscape-fcose';
import { enrichNodesWithGraphIcons } from '../graph_node_icons.js';

cytoscape.use(fcose);

/**
 * Options fCoSE lisibles sur 50–200 nœuds.
 * - quality « draft » = seulement le placement spectral → amas illisible ; on préfère « proof ».
 * - nodeDimensionsIncludeLabels (proof) : le placement tient compte des libellés, moins de chevauchement texte.
 */
function fcoseLayoutOptions(overrides = {}) {
    return {
        name: 'fcose',
        quality: 'proof',
        randomize: true,
        animate: false,
        fit: false,
        padding: 88,
        nodeDimensionsIncludeLabels: true,
        nodeSeparation: 220,
        idealEdgeLength: () => 110,
        nodeRepulsion: () => 48000,
        edgeElasticity: () => 0.42,
        gravity: 0.06,
        gravityRange: 5.2,
        numIter: 3200,
        ...overrides,
    };
}

export default class extends Controller {
    static values = {
        url: String,
        query: String,
        locale: { type: String, default: 'en' },
        msgLoading: String,
        msgError: String,
        msgSelection: String,
        msgViewFull: String,
    };

    static targets = ['canvas', 'status', 'preview', 'layoutSelect', 'toolbar'];

    connect() {
        this.cy = null;
        void this.load();
    }

    disconnect() {
        if (this.cy) {
            this.cy.destroy();
            this.cy = null;
        }
    }

    async load() {
        const base = this.urlValue;
        const qs = this.queryValue || '';
        const url = qs ? `${base}?${qs}` : base;
        this.statusTarget.textContent = this.msgLoadingValue || '';
        try {
            const res = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            if (!res.ok) {
                throw new Error(`HTTP ${res.status}`);
            }
            const data = await res.json();
            this.statusTarget.textContent = '';
            try {
                this.renderGraph(data.elements || { nodes: [], edges: [] });
            } catch {
                this.statusTarget.textContent = this.msgErrorValue || '';
            }
        } catch (e) {
            this.statusTarget.textContent = this.msgErrorValue || '';
        }
    }

    renderGraph(elements) {
        if (this.cy) {
            this.cy.destroy();
        }
        enrichNodesWithGraphIcons(elements.nodes || [], null);
        const flat = [
            ...(elements.nodes || []).map((n) => ({ group: 'nodes', ...n })),
            ...(elements.edges || []).map((e) => ({ group: 'edges', ...e })),
        ];
        const loc = this.localeValue || 'en';
        const sel = this.msgSelectionValue || 'Selection';
        const viewFull = this.msgViewFullValue || 'View full profile';

        this.cy = cytoscape({
            container: this.canvasTarget,
            elements: flat,
            style: [
                {
                    selector: 'node',
                    style: {
                        shape: 'roundrectangle',
                        label: 'data(label)',
                        'text-valign': 'bottom',
                        'text-halign': 'center',
                        'text-margin-y': 5,
                        'font-size': 9,
                        'min-zoomed-font-size': 6,
                        'font-family': 'system-ui, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
                        color: '#E8ECF1',
                        'text-outline-width': 2,
                        'text-outline-color': '#0E1218',
                        width: 18,
                        height: 18,
                        'background-color': 'transparent',
                        'background-image': 'data(iconImage)',
                        'background-fit': 'contain',
                        'background-repeat': 'no-repeat',
                        'background-position-x': '50%',
                        'background-position-y': '50%',
                        'background-clip': 'node',
                        'border-width': 1,
                        'border-color': '#2A3038',
                    },
                },
                {
                    selector: 'node.gg-hover',
                    style: {
                        'font-size': 11,
                        'text-outline-width': 3,
                        'border-width': 2,
                        'border-color': '#8B95A6',
                    },
                },
                {
                    selector: 'edge',
                    style: {
                        width: 1.25,
                        'line-color': '#6F7A8C',
                        'curve-style': 'straight',
                        opacity: 0.72,
                        'target-arrow-shape': 'none',
                    },
                },
            ],
            minZoom: 0.08,
            maxZoom: 4.5,
            wheelSensitivity: 0.28,
        });

        const runLayout = () => {
            const layout = this.cy.layout(fcoseLayoutOptions());
            layout.on('layoutstop', () => {
                this.cy.fit(undefined, 96);
            });
            layout.run();
        };
        runLayout();

        this.cy.on('mouseover', 'node', (evt) => {
            evt.target.addClass('gg-hover');
        });
        this.cy.on('mouseout', 'node', (evt) => {
            evt.target.removeClass('gg-hover');
        });

        this.cy.on('tap', 'node', (evt) => {
            const n = evt.target;
            const slug = n.data('slug');
            const label = n.data('label');
            const type = n.data('type');
            if (slug && typeof slug === 'string') {
                const path =
                    type === 'organization'
                        ? `/${encodeURIComponent(loc)}/organizations/${encodeURIComponent(slug)}`
                        : `/${encodeURIComponent(loc)}/people/${encodeURIComponent(slug)}`;
                const safeLabel =
                    typeof label === 'string'
                        ? label.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;')
                        : '';
                this.previewTarget.innerHTML = `<h2 class="font-serif text-lg font-medium text-text-primary">${sel}</h2>
                    <p class="mt-2 text-text-primary">${safeLabel}</p>
                    <a href="${path}" class="mt-3 inline-block rounded-sm border border-accent bg-transparent px-3 py-2 font-medium text-accent no-underline shadow-none transition-shadow duration-150 hover:bg-accent-subtle hover:shadow-subtle">${viewFull}</a>`;
            }
        });
    }

    zoomIn() {
        this.cy?.zoom(this.cy.zoom() * 1.2);
        this.cy?.center();
    }

    zoomOut() {
        this.cy?.zoom(this.cy.zoom() * 0.8);
        this.cy?.center();
    }

    fit() {
        this.cy?.fit(undefined, 96);
    }

    reset() {
        this.cy?.reset();
        void this.load();
    }

    changeLayout() {
        const name = this.layoutSelectTarget.value;
        if (name === 'fcose') {
            const opts = fcoseLayoutOptions({ animate: true, fit: false });
            const layout = this.cy?.layout(opts);
            layout?.on('layoutstop', () => {
                this.cy?.fit(undefined, 96);
            });
            layout?.run();
            return;
        }
        const layout = this.cy?.layout({ name, animate: true, fit: true, padding: 80 });
        layout?.on('layoutstop', () => {
            this.cy?.fit(undefined, 96);
        });
        layout?.run();
    }
}
