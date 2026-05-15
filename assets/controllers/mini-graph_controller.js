import { Controller } from '@hotwired/stimulus';
import cytoscape from 'cytoscape';
import fcose from 'cytoscape-fcose';
import { enrichNodesWithGraphIcons } from '../graph_node_icons.js';

cytoscape.use(fcose);

/** Même recette que le graphe global (global_graph_controller.js) pour lisibilité sur 50–200 nœuds. */
function fcoseLayoutOptions(overrides = {}) {
    return {
        name: 'fcose',
        quality: 'proof',
        randomize: true,
        animate: false,
        fit: false,
        padding: 48,
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

/** Gris unique pour les personnes (mini-graphes fiches). Même valeur que GraphDataBuilder::PERSON_NODE_FILL. */
const PERSON_NODE_FILL = '#6F7A8C';

const ORG_TYPE_COLOR = {
    influence_network: '#7B1A1A',
    political_party: '#1A2C5B',
    corporation: '#A84B27',
    media_group: '#5C1A6B',
    government_body: '#1F4D3F',
    international_body: '#0F4D5B',
    think_tank: '#854F0B',
    lobby_group: '#5C1313',
    other: '#5A5650',
};

export default class extends Controller {
    static values = {
        url: String,
        locale: { type: String, default: 'en' },
        /** Nœud Cytoscape à centrer après layout (ex. person-12, org-3). */
        focusNodeId: String,
        msgAnalyzing: String,
        msgLoadError: String,
        msgConnectionsZero: String,
        msgConnectionsOne: String,
        msgConnectionsMany: String,
    };

    static targets = ['skeleton', 'canvas', 'analyzing', 'count'];

    connect() {
        this.cy = null;
        this.loaded = false;
        this.observer = new IntersectionObserver(
            (entries) => {
                for (const entry of entries) {
                    if (entry.isIntersecting && !this.loaded) {
                        this.loaded = true;
                        void this.loadGraph();
                    }
                }
            },
            { root: null, rootMargin: '0px', threshold: 0.12 },
        );
        this.observer.observe(this.element);
    }

    disconnect() {
        this.observer?.disconnect();
        this.destroyCy();
    }

    destroyCy() {
        if (this.cy) {
            this.cy.destroy();
            this.cy = null;
        }
    }

    async loadGraph() {
        const url = this.urlValue;
        if (!url) {
            return;
        }

        try {
            const res = await fetch(url, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });
            if (!res.ok) {
                throw new Error(`HTTP ${res.status}`);
            }
            /** @type {{ analyzing?: boolean, connectionCount?: number, elements?: { nodes: unknown[], edges: unknown[] } }} */
            const data = await res.json();

            if (this.hasCountTarget) {
                let n = typeof data.connectionCount === 'number' ? data.connectionCount : null;
                if (n === null && data.elements && Array.isArray(data.elements.edges)) {
                    n = data.elements.edges.length;
                }
                if (typeof n === 'number') {
                    if (n === 0 && this.hasMsgConnectionsZeroValue) {
                        this.countTarget.textContent = this.msgConnectionsZeroValue;
                    } else if (n === 1 && this.hasMsgConnectionsOneValue) {
                        this.countTarget.textContent = this.msgConnectionsOneValue;
                    } else if (this.hasMsgConnectionsManyValue) {
                        this.countTarget.textContent = this.msgConnectionsManyValue.replace('%count%', String(n));
                    } else {
                        this.countTarget.textContent = String(n);
                    }
                }
            }

            if (data.analyzing) {
                if (this.hasCountTarget) {
                    this.countTarget.textContent = '—';
                }
                this.skeletonTarget.classList.add('hidden');
                if (this.hasAnalyzingTarget) {
                    this.analyzingTarget.textContent = this.msgAnalyzingValue || '';
                    this.analyzingTarget.classList.remove('hidden');
                }
                return;
            }

            const elements = data.elements;
            if (elements) {
                this.enrichNodeColors(elements);
                enrichNodesWithGraphIcons(
                    elements.nodes || [],
                    this.hasFocusNodeIdValue && this.focusNodeIdValue ? this.focusNodeIdValue : null,
                );
            }

            this.skeletonTarget.classList.add('hidden');
            if (this.hasAnalyzingTarget) {
                this.analyzingTarget.classList.add('hidden');
            }
            if (this.hasCanvasTarget && elements) {
                this.canvasTarget.classList.remove('hidden');
                this.initCytoscape(elements);
            }
        } catch {
            this.skeletonTarget.classList.add('hidden');
            if (this.hasAnalyzingTarget) {
                this.analyzingTarget.textContent = this.msgLoadErrorValue || '';
                this.analyzingTarget.classList.remove('hidden');
            }
        }
    }

    /**
     * @param {{ nodes?: { data?: Record<string, unknown> }[], edges?: unknown[] }} elements
     */
    enrichNodeColors(elements) {
        for (const el of elements.nodes || []) {
            const d = el.data;
            if (!d || typeof d !== 'object') {
                continue;
            }
            if (d.type === 'person') {
                d.bgColor = PERSON_NODE_FILL;
            } else if (d.type === 'organization') {
                const t = typeof d.orgType === 'string' ? d.orgType : '';
                d.bgColor = ORG_TYPE_COLOR[t] || ORG_TYPE_COLOR.other;
            }
        }
    }

    /**
     * @param {{ nodes: object[], edges: object[] }} elements
     */
    initCytoscape(elements) {
        this.destroyCy();
        const flat = [
            ...(elements.nodes || []).map((n) => ({ group: 'nodes', ...n })),
            ...(elements.edges || []).map((e) => ({ group: 'edges', ...e })),
        ];

        const loc = this.localeValue || 'en';

        const edgeCount = elements.edges?.length ?? 0;
        /** fcose peut échouer ou mal se comporter sans arêtes (ex. org. sans membres). */
        const layout =
            edgeCount === 0
                ? { name: 'circle', fit: true, padding: 20, spacingFactor: 1.25 }
                : fcoseLayoutOptions();

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
                        'text-margin-y': 6,
                        'font-size': 10,
                        'font-family': 'system-ui, sans-serif',
                        color: '#E8ECF1',
                        'background-color': 'transparent',
                        'background-image': 'data(iconImage)',
                        'background-fit': 'contain',
                        'background-repeat': 'no-repeat',
                        'background-position-x': '50%',
                        'background-position-y': '50%',
                        'background-clip': 'node',
                        width: 28,
                        height: 28,
                        'border-width': 1,
                        'border-color': '#2A3038',
                    },
                },
                {
                    selector: 'node.central',
                    style: {
                        width: 46,
                        height: 46,
                        'font-size': 13,
                        'font-weight': 'bold',
                        'min-zoomed-font-size': 8,
                        'border-width': 2,
                        'border-color': '#5DCAA5',
                        'background-color': 'transparent',
                        color: '#E8ECF1',
                        'text-outline-width': 0,
                    },
                },
                {
                    selector: 'edge',
                    style: {
                        width: 1.5,
                        'line-color': '#8B95A6',
                        'target-arrow-color': '#8B95A6',
                        'target-arrow-shape': 'triangle',
                        'curve-style': 'bezier',
                        opacity: 0.85,
                    },
                },
            ],
            wheelSensitivity: 0.35,
            minZoom: 0.08,
            maxZoom: 4.5,
        });

        const runLayout = () => {
            if (edgeCount === 0) {
                this.cy.layout(layout).run();
                this.applyFocusViewport();
                return;
            }
            const l = this.cy.layout(layout);
            l.on('layoutstop', () => {
                this.applyFocusViewport();
            });
            l.run();
        };
        runLayout();

        this.cy.on('tap', 'node', (evt) => {
            const node = evt.target;
            const type = node.data('type');
            const slug = node.data('slug');
            if (!slug || typeof slug !== 'string') {
                return;
            }
            if (type === 'person') {
                window.location.href = `/${encodeURIComponent(loc)}/people/${encodeURIComponent(slug)}`;
            } else if (type === 'organization') {
                window.location.href = `/${encodeURIComponent(loc)}/organizations/${encodeURIComponent(slug)}`;
            }
        });
    }

    applyFocusViewport() {
        if (!this.cy || !this.hasFocusNodeIdValue || !this.focusNodeIdValue) {
            return;
        }
        const el = this.cy.getElementById(this.focusNodeIdValue);
        if (!el || el.empty()) {
            return;
        }
        el.addClass('central');
        const id = this.focusNodeIdValue;
        const isOrg = id.startsWith('org-');

        if (isOrg) {
            const nhood = el.closedNeighborhood();
            this.cy.fit(nhood, 40);
            this.cy.center(el);
            return;
        }

        const nhood = el.closedNeighborhood();
        this.cy.fit(nhood, 48);
        this.cy.center(el);
        const z = this.cy.zoom();
        this.cy.zoom(Math.min(this.cy.maxZoom(), Math.max(this.cy.minZoom(), z * 1.12)));
        this.cy.center(el);
    }
}
