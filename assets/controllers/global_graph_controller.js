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
        msgViewFull: String,
        msgKindPerson: String,
        msgKindOrganization: String,
        msgLabelCategory: String,
        msgLabelCountries: String,
        msgLabelOrgType: String,
        roleLabels: { type: Object, default: {} },
        orgTypeLabels: { type: Object, default: {} },
        focusSlug: { type: String, default: '' },
    };

    static targets = ['canvas', 'status', 'layoutSelect', 'toolbar', 'nodeDialog', 'dialogTitle', 'dialogKind', 'dialogLine1', 'dialogLine2', 'dialogLink'];

    connect() {
        this.cy = null;
        this._focusNodeModalDone = false;
        void this.load();
    }

    disconnect() {
        if (this.hasNodeDialogTarget && this.nodeDialogTarget.open) {
            this.nodeDialogTarget.close();
        }
        if (this.cy) {
            this.cy.destroy();
            this.cy = null;
        }
    }

    async load() {
        this._focusNodeModalDone = false;
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

        const layout = this.cy.layout(fcoseLayoutOptions());
        layout.on('layoutstop', () => {
            this.cy.fit(undefined, 96);
            this.maybeOpenFocusNode();
        });
        layout.run();

        this.cy.on('mouseover', 'node', (evt) => {
            evt.target.addClass('gg-hover');
        });
        this.cy.on('mouseout', 'node', (evt) => {
            evt.target.removeClass('gg-hover');
        });

        this.cy.on('tap', 'node', (evt) => {
            this.openNodeDialog(evt.target);
        });
    }

    openNodeDialog(node) {
        if (!this.hasNodeDialogTarget) {
            return;
        }
        const d = node.data();
        const slug = d.slug;
        if (!slug || typeof slug !== 'string') {
            return;
        }
        const loc = this.localeValue || 'en';
        const type = d.type === 'organization' ? 'organization' : 'person';
        const path =
            type === 'organization'
                ? `/${encodeURIComponent(loc)}/organizations/${encodeURIComponent(slug)}`
                : `/${encodeURIComponent(loc)}/people/${encodeURIComponent(slug)}`;

        const label = typeof d.label === 'string' ? d.label : '';
        this.dialogTitleTarget.textContent = label;

        if (type === 'organization') {
            this.dialogKindTarget.textContent = this.msgKindOrganizationValue || '';
            const orgType = typeof d.orgType === 'string' ? d.orgType : '';
            const orgTypeHuman = this.orgTypeLabelsValue[orgType] ?? orgType;
            this.dialogLine1Target.textContent = `${this.msgLabelOrgTypeValue || 'Type'} : ${orgTypeHuman || '—'}`;
            this.dialogLine2Target.textContent = '';
            this.dialogLine2Target.classList.add('hidden');
        } else {
            this.dialogKindTarget.textContent = this.msgKindPersonValue || '';
            const cat = typeof d.category === 'string' ? d.category : '';
            const catHuman = this.roleLabelsValue[cat] ?? cat;
            this.dialogLine1Target.textContent = `${this.msgLabelCategoryValue || 'Category'} : ${catHuman || '—'}`;
            const codes = Array.isArray(d.countryCodes) ? d.countryCodes.filter((c) => typeof c === 'string' && c !== '') : [];
            if (codes.length > 0) {
                this.dialogLine2Target.textContent = `${this.msgLabelCountriesValue || 'Countries'} : ${codes.join(', ')}`;
                this.dialogLine2Target.classList.remove('hidden');
            } else {
                this.dialogLine2Target.textContent = '';
                this.dialogLine2Target.classList.add('hidden');
            }
        }

        this.dialogLinkTarget.href = path;
        this.dialogLinkTarget.textContent = this.msgViewFullValue || '';

        this.nodeDialogTarget.showModal();
    }

    maybeOpenFocusNode() {
        if (this._focusNodeModalDone || !this.cy) {
            return;
        }
        const slug = (this.focusSlugValue || '').trim();
        if (!slug) {
            return;
        }
        const found = this.cy.nodes().filter((ele) => ele.data('slug') === slug).first();
        if (found.empty()) {
            return;
        }
        this._focusNodeModalDone = true;
        this.openNodeDialog(found);
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
