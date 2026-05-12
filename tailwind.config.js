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
                xs: '2px',
            },
        },
    },
    plugins: [],
};
