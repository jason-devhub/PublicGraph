import { Controller } from '@hotwired/stimulus';
import cytoscape from 'cytoscape';
import fcose from 'cytoscape-fcose';

cytoscape.use(fcose);

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
            this.renderGraph(data.elements || { nodes: [], edges: [] });
            this.statusTarget.textContent = '';
        } catch (e) {
            this.statusTarget.textContent = this.msgErrorValue || '';
        }
    }

    renderGraph(elements) {
        if (this.cy) {
            this.cy.destroy();
        }
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
                        label: 'data(label)',
                        'font-size': 10,
                        color: '#E8E4DC',
                        'text-outline-width': 2,
                        'text-outline-color': '#0D0F12',
                        width: 22,
                        height: 22,
                        'background-color': 'data(bgColor)',
                    },
                },
                {
                    selector: 'edge',
                    style: {
                        width: 2,
                        'line-color': '#5A5650',
                        'curve-style': 'haystack',
                        opacity: 0.55,
                    },
                },
            ],
            layout: { name: 'fcose', animate: false, randomize: true },
            wheelSensitivity: 0.35,
        });
        this.cy.on('tap', 'node', (evt) => {
            const n = evt.target;
            const slug = n.data('slug');
            const label = n.data('label');
            if (slug) {
                this.previewTarget.innerHTML = `<h2 class="font-serif text-lg font-medium text-text-primary">${sel}</h2>
                    <p class="mt-2 text-text-primary">${label}</p>
                    <a href="/${encodeURIComponent(loc)}/people/${encodeURIComponent(slug)}" class="mt-3 inline-block border border-accent px-3 py-2 text-accent no-underline hover:bg-accent-subtle">${viewFull}</a>`;
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
        this.cy?.fit(undefined, 40);
    }

    reset() {
        this.cy?.reset();
        void this.load();
    }

    changeLayout() {
        const name = this.layoutSelectTarget.value;
        const layout = this.cy?.layout({ name, animate: true });
        layout?.run();
    }
}
