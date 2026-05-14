/** @type {import('tailwindcss').Config} */
/* Tokens alignés sur specs/design.md §2 — pas de palette Tailwind par défaut (ex. blue-500). */
module.exports = {
    content: [
        './templates/**/*.twig',
        './assets/**/*.js',
        './src/**/*.php',
    ],
    theme: {
        extend: {
            colors: {
                surface: {
                    paper: '#FAF7F2',
                    primary: '#FFFFFF',
                    secondary: '#F4F0E8',
                    inset: '#EDE8DD',
                },
                ink: {
                    DEFAULT: '#0E1218',
                    text: '#E8ECF1',
                    accent: '#5DCAA5',
                    secondary: '#8B95A6',
                },
                text: {
                    primary: '#1A1815',
                    secondary: '#5A5650',
                    tertiary: '#8A8680',
                    inverse: '#FAF7F2',
                },
                rule: {
                    strong: '#1A1815',
                    medium: '#C8C2B5',
                    subtle: '#E5DFD2',
                },
                accent: {
                    DEFAULT: '#7B1A1A',
                    hover: '#5C1313',
                    subtle: '#F2E5E5',
                },
                cat: {
                    politician: '#1A2C5B',
                    civil_servant: '#5A5650',
                    business_leader: '#A84B27',
                    media_owner: '#5C1A6B',
                    financier: '#1F4D3F',
                    lobbyist: '#7B1A1A',
                    other_influencer: '#8A8680',
                },
                orgtype: {
                    influence_network: '#7B1A1A',
                    political_party: '#1A2C5B',
                    corporation: '#A84B27',
                    media_group: '#5C1A6B',
                    government_body: '#1F4D3F',
                    international_body: '#0F4D5B',
                    think_tank: '#854F0B',
                    lobby_group: '#5C1313',
                    other: '#5A5650',
                },
                status: {
                    'pending-bg': '#FAEEDA',
                    'pending-fg': '#854F0B',
                    'approved-bg': '#EAF3DE',
                    'approved-fg': '#3B6D11',
                    'rejected-bg': '#FCEBEB',
                    'rejected-fg': '#A32D2D',
                    'neutral-bg': '#EDE8DD',
                    'neutral-fg': '#5A5650',
                },
            },
            fontFamily: {
                /* RGPD : pas de Google Fonts ; stack système (T1.6). Self-host Newsreader/Public Sans prévu design.md. */
                sans: ['system-ui', 'Segoe UI', 'Roboto', 'Helvetica Neue', 'Arial', 'sans-serif'],
                serif: ['ui-serif', 'Georgia', 'Cambria', 'Times New Roman', 'serif'],
            },
            borderRadius: {
                /* specs/design.md §2.4 */
                xs: '2px',
                sm: '4px',
                md: '6px',
                lg: '12px',
            },
            boxShadow: {
                /* specs/design.md §2.6 + chaleur éditoriale (pas de dégradés sur les fonds) */
                subtle: '0 1px 2px rgba(26, 24, 21, 0.04)',
                modal: '0 8px 32px rgba(26, 24, 21, 0.12)',
                'focus-ring': '0 0 0 3px rgba(123, 26, 26, 0.25)',
                'header-bar':
                    '0 1px 0 rgba(255, 255, 255, 0.65) inset, 0 1px 0 rgba(200, 194, 181, 0.5), 0 8px 24px -6px rgba(26, 24, 21, 0.07)',
                'btn-primary':
                    '0 1px 0 rgba(255, 255, 255, 0.14) inset, 0 1px 1px rgba(0, 0, 0, 0.1), 0 4px 12px -2px rgba(91, 19, 19, 0.28)',
                'btn-primary-hover':
                    '0 1px 0 rgba(255, 255, 255, 0.18) inset, 0 2px 3px rgba(0, 0, 0, 0.12), 0 8px 20px -4px rgba(91, 19, 19, 0.32)',
                'btn-primary-active': 'inset 0 2px 4px rgba(0, 0, 0, 0.22)',
                'input-inset':
                    'inset 0 1px 2px rgba(26, 24, 21, 0.05), 0 1px 0 rgba(255, 255, 255, 0.75)',
                'input-focus':
                    'inset 0 1px 2px rgba(26, 24, 21, 0.06), 0 0 0 1px rgba(123, 26, 26, 0.35), 0 0 0 4px rgba(123, 26, 26, 0.12)',
                'photo-frame':
                    '0 0 0 1px rgba(255, 255, 255, 0.55), 0 1px 1px rgba(26, 24, 21, 0.06), 0 6px 20px -4px rgba(26, 24, 21, 0.12), inset 0 1px 0 rgba(255, 255, 255, 0.45)',
                'btn-ink':
                    '0 1px 0 rgba(255, 255, 255, 0.06) inset, 0 1px 2px rgba(0, 0, 0, 0.35), 0 4px 14px -2px rgba(0, 0, 0, 0.45)',
                'btn-ink-hover':
                    '0 1px 0 rgba(255, 255, 255, 0.08) inset, 0 2px 4px rgba(0, 0, 0, 0.4), 0 8px 22px -4px rgba(0, 0, 0, 0.5)',
            },
            maxWidth: {
                content: '680px',
                page: '1200px',
                'page-wide': '1440px',
            },
            screens: {
                xs: '380px',
            },
        },
    },
    plugins: [],
};
