import { Controller } from '@hotwired/stimulus';
import cytoscape from 'cytoscape';
import fcose from 'cytoscape-fcose';

cytoscape.use(fcose);

/** Couleurs catégories personne / types org — specs/design.md §2.1 */
const PERSON_CATEGORY_COLOR = {
    politician: '#1A2C5B',
    civil_servant: '#5A5650',
    business_leader: '#A84B27',
    media_owner: '#5C1A6B',
    financier: '#1F4D3F',
    lobbyist: '#7B1A1A',
    other_influencer: '#8A8680',
};

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

            if (this.hasCountTarget && typeof data.connectionCount === 'number') {
                const n = data.connectionCount;
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
                const c = typeof d.category === 'string' ? d.category : '';
                d.bgColor = PERSON_CATEGORY_COLOR[c] || PERSON_CATEGORY_COLOR.other_influencer;
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
                : {
                      name: 'fcose',
                      quality: 'default',
                      randomize: true,
                      animate: false,
                      fit: true,
                      padding: 12,
                  };

        this.cy = cytoscape({
            container: this.canvasTarget,
            elements: flat,
            style: [
                {
                    selector: 'node',
                    style: {
                        label: 'data(label)',
                        'text-valign': 'bottom',
                        'text-margin-y': 6,
                        'font-size': 10,
                        'font-family': 'system-ui, sans-serif',
                        color: '#E8ECF1',
                        'background-color': 'data(bgColor)',
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
                        'font-size': 11,
                        'border-width': 2,
                        'border-color': '#5DCAA5',
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
            layout,
            wheelSensitivity: 0.35,
        });

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
}
