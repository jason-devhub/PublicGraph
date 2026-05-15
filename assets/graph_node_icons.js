/**
 * Icônes SVG pour nœuds Cytoscape (personne / organisation).
 * Les organisations varient selon orgType (parti, entreprise, réseau d’influence…).
 */

/**
 * @param {string} v
 * @returns {string}
 */
function sanitizeBgColor(v) {
    if (typeof v !== 'string' || v.length < 3) {
        return '#5A5650';
    }
    if (/^#[0-9A-Fa-f]{6}$/i.test(v)) {
        return v;
    }
    if (/^hsl\s*\(/i.test(v)) {
        return v;
    }

    return '#5A5650';
}

/**
 * @param {string} orgType
 * @returns {string}
 */
function normalizeOrgType(orgType) {
    if (typeof orgType !== 'string' || orgType.length === 0) {
        return 'other';
    }
    const known = new Set([
        'influence_network',
        'political_party',
        'corporation',
        'media_group',
        'government_body',
        'international_body',
        'think_tank',
        'lobby_group',
        'other',
    ]);

    return known.has(orgType) ? orgType : 'other';
}

/**
 * @param {{ data?: Record<string, unknown>, classes?: string }} node
 * @param {string | null} focusNodeId
 */
function isCentralNode(node, focusNodeId) {
    const c = node.classes;
    if (typeof c === 'string' && c.split(/\s+/).includes('central')) {
        return true;
    }
    const id = node.data && typeof node.data.id === 'string' ? node.data.id : null;
    if (focusNodeId && id === focusNodeId) {
        return true;
    }

    return false;
}

const PAPER = 'rgba(250,247,242,0.94)';

/** Gris neutre pour toutes les personnes (graphes). Même valeur que GraphDataBuilder::PERSON_NODE_FILL. */
const PERSON_NODE_FILL = '#6F7A8C';

/**
 * @param {string} g — couleur du glyphe (traits / formes)
 */
function wrap32(central, bg, inner) {
    if (central) {
        return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" width="32" height="32">
  <rect x="1" y="1" width="30" height="30" rx="8" fill="${bg}"/>
  ${inner}
</svg>`;
    }

    return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" width="32" height="32">
  <rect x="1" y="1" width="30" height="30" rx="7" fill="${bg}"/>
  ${inner}
</svg>`;
}

/**
 * @param {string} bg
 * @param {boolean} central
 */
function svgPerson(bg, central) {
    if (central) {
        return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" width="32" height="32">
  <rect x="1" y="1" width="30" height="30" rx="8" fill="#FAF7F2"/>
  <circle cx="16" cy="12.5" r="4.3" fill="${bg}"/>
  <path fill="${bg}" d="M10 30v-6.2q0-5.5 6-5.5t6 5.5V30z"/>
</svg>`;
    }

    return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" width="32" height="32">
  <rect x="1" y="1" width="30" height="30" rx="7" fill="${bg}"/>
  <circle cx="16" cy="12.5" r="4.3" fill="${PAPER}"/>
  <path fill="${PAPER}" d="M10 30v-6.2q0-5.5 6-5.5t6 5.5V30z"/>
</svg>`;
}

/** Réseau d’influence : trois nœuds reliés */
function innerInfluenceNetwork(g) {
    return `<circle cx="11" cy="12" r="2.6" fill="none" stroke="${g}" stroke-width="1.6"/>
  <circle cx="21" cy="12" r="2.6" fill="none" stroke="${g}" stroke-width="1.6"/>
  <circle cx="16" cy="20.5" r="2.6" fill="none" stroke="${g}" stroke-width="1.6"/>
  <path fill="none" stroke="${g}" stroke-width="1.35" stroke-linecap="round" d="M12.5 13.8l2.5 4.8M19.5 13.8l-2.5 4.8M13.8 19.8h4.4"/>`;
}

/** Parti : mât + drapeau */
function innerPoliticalParty(g) {
    return `<rect x="11" y="9" width="2.2" height="16" rx="0.5" fill="${g}"/>
  <path fill="${g}" d="M13.5 9.5L23 12l-9.5 3.2V9.5z"/>`;
}

/** Entreprise / multinationale : deux tours + antenne */
function innerCorporation(g, hole) {
    return `<rect x="9" y="10" width="5" height="18" rx="1" fill="${g}"/>
  <rect x="10.2" y="13" width="1.2" height="1.2" fill="${hole}"/>
  <rect x="11.8" y="13" width="1.2" height="1.2" fill="${hole}"/>
  <rect x="18" y="7" width="6" height="21" rx="1" fill="${g}"/>
  <rect x="19.2" y="10" width="1.2" height="1.2" fill="${hole}"/>
  <rect x="20.8" y="10" width="1.2" height="1.2" fill="${hole}"/>
  <rect x="19.2" y="13.5" width="1.2" height="1.2" fill="${hole}"/>
  <path fill="none" stroke="${g}" stroke-width="1.2" stroke-linecap="round" d="M21 7V5"/>`;
}

/** Média : cadre + antenne */
function innerMediaGroup(g) {
    return `<path fill="none" stroke="${g}" stroke-width="1.4" stroke-linecap="round" d="M13 7l3-2 3 2"/>
  <rect x="9" y="9" width="14" height="11" rx="1.5" fill="none" stroke="${g}" stroke-width="1.8"/>
  <rect x="11" y="11" width="10" height="7" rx="0.5" fill="${g}" opacity="0.38"/>
  <path fill="none" stroke="${g}" stroke-width="1.2" stroke-linecap="round" d="M12 23h8"/>`;
}

/** Gouvernement : fronton + colonnes */
function innerGovernmentBody(g, hole) {
    return `<path fill="${g}" d="M8 14L16 8l8 6v14H8V14z"/>
  <rect x="11" y="18" width="2" height="8" fill="${hole}"/>
  <rect x="15" y="18" width="2" height="8" fill="${hole}"/>
  <rect x="19" y="18" width="2" height="8" fill="${hole}"/>`;
}

/** International : globe */
function innerInternationalBody(g) {
    return `<circle cx="16" cy="16" r="9" fill="none" stroke="${g}" stroke-width="1.8"/>
  <ellipse cx="16" cy="16" rx="3.2" ry="9" fill="none" stroke="${g}" stroke-width="1.4"/>
  <path fill="none" stroke="${g}" stroke-width="1.4" d="M7 16h18"/>`;
}

/** Think tank : ampoule */
function innerThinkTank(g) {
    return `<path fill="${g}" d="M16 7a6.2 6.2 0 0 1 3.2 11.5c-.6.5-1 1.1-1.2 1.8h-4c-.2-.7-.6-1.3-1.2-1.8A6.2 6.2 0 0 1 16 7z"/>
  <path fill="none" stroke="${g}" stroke-width="1.3" d="M12.5 22.5h7"/>
  <path fill="${g}" d="M13 24h6v1.5H13z"/>`;
}

/** Lobby : mallette */
function innerLobbyGroup(g, hole) {
    return `<rect x="9" y="13" width="14" height="11" rx="1.5" fill="${g}"/>
  <path fill="none" stroke="${g}" stroke-width="1.4" d="M12 13v-2.5a4 4 0 0 1 8 0V13"/>
  <rect x="14" y="17" width="4" height="4" rx="0.5" fill="${hole}"/>`;
}

/** Bâtiment générique */
function innerOrganizationGeneric(g, hole) {
    return `<path fill="${g}" d="M8 13L16 7l8 6v16H8V13z"/>
  <rect x="14" y="19" width="4" height="10" fill="${hole}"/>
  <rect x="10.5" y="15" width="2.8" height="2.8" fill="${hole}"/>
  <rect x="14.6" y="15" width="2.8" height="2.8" fill="${hole}"/>
  <rect x="18.7" y="15" width="2.8" height="2.8" fill="${hole}"/>`;
}

/**
 * @param {string} orgType
 * @param {string} bg
 * @param {boolean} central
 */
function svgOrganizationBySubtype(orgType, bg, central) {
    const t = normalizeOrgType(orgType);
    /** Même recette que le nœud non central : fond type (bg) + glyphes clairs. Le cadre « central » est surtout plus grand côté Cytoscape. */
    const g = PAPER;
    const hole = bg;

    switch (t) {
        case 'influence_network':
            return wrap32(central, bg, innerInfluenceNetwork(g));
        case 'political_party':
            return wrap32(central, bg, innerPoliticalParty(g));
        case 'corporation':
            return wrap32(central, bg, innerCorporation(g, hole));
        case 'media_group':
            return wrap32(central, bg, innerMediaGroup(g));
        case 'government_body':
            return wrap32(central, bg, innerGovernmentBody(g, hole));
        case 'international_body':
            return wrap32(central, bg, innerInternationalBody(g));
        case 'think_tank':
            return wrap32(central, bg, innerThinkTank(g));
        case 'lobby_group':
            return wrap32(central, bg, innerLobbyGroup(g, hole));
        default:
            return wrap32(central, bg, innerOrganizationGeneric(g, hole));
    }
}

/**
 * @param {'person' | 'organization'} type
 * @param {string} bgColor
 * @param {boolean} central
 * @param {string} [orgType]
 * @returns {string}
 */
export function cytoscapeNodeBackgroundImage(type, bgColor, central, orgType = 'other') {
    const bg = sanitizeBgColor(bgColor);
    if (type === 'organization') {
        return `url("data:image/svg+xml;charset=utf-8,${encodeURIComponent(svgOrganizationBySubtype(orgType, bg, central))}")`;
    }
    const svg = svgPerson(bg, central);

    return `url("data:image/svg+xml;charset=utf-8,${encodeURIComponent(svg)}")`;
}

/**
 * @param {Array<{ data?: Record<string, unknown>, classes?: string }>} nodes
 * @param {string | null} focusNodeId
 */
export function enrichNodesWithGraphIcons(nodes, focusNodeId = null) {
    for (const node of nodes) {
        const d = node.data;
        if (!d || typeof d !== 'object') {
            continue;
        }
        const ty = d.type;
        if (ty !== 'person' && ty !== 'organization') {
            continue;
        }
        const central = isCentralNode(node, focusNodeId);
        const orgType = typeof d.orgType === 'string' ? d.orgType : 'other';
        if (ty === 'organization') {
            const rawBg = typeof d.bgColor === 'string' ? d.bgColor : '#5A5650';
            d.iconImage = cytoscapeNodeBackgroundImage('organization', rawBg, central, orgType);
        } else {
            d.iconImage = cytoscapeNodeBackgroundImage('person', PERSON_NODE_FILL, central);
        }
    }
}
