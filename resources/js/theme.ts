import { createTheme, responsiveFontSizes } from '@mui/material/styles';

let theme = createTheme({
    palette: {
        mode: 'light',
        primary: { main: '#0b4d3b', dark: '#06372b', light: '#e7f2ed', contrastText: '#fff' },
        secondary: { main: '#b58a36', dark: '#7e5d1f', contrastText: '#fff' },
        error: { main: '#b42318' },
        background: { default: '#fbfaf6', paper: '#ffffff' },
        text: { primary: '#17231f', secondary: '#5b6661' },
    },
    typography: {
        fontFamily: '"Inter", "Noto Sans Bengali", system-ui, sans-serif',
        h1: { fontFamily: '"Newsreader", "Noto Serif Bengali", serif', fontWeight: 650, letterSpacing: '-0.035em', lineHeight: 1.04 },
        h2: { fontFamily: '"Newsreader", "Noto Serif Bengali", serif', fontWeight: 650, letterSpacing: '-0.025em', lineHeight: 1.1 },
        h3: { fontFamily: '"Newsreader", "Noto Serif Bengali", serif', fontWeight: 650 },
        button: { fontWeight: 700, textTransform: 'none' },
    },
    shape: { borderRadius: 14 },
    spacing: 8,
    components: {
        MuiButton: { styleOverrides: { root: { borderRadius: 999, paddingInline: 22, minHeight: 44 } } },
        MuiCard: { styleOverrides: { root: { boxShadow: '0 18px 60px rgba(14, 52, 41, .08)', border: '1px solid rgba(11,77,59,.08)' } } },
        MuiTextField: { defaultProps: { size: 'small' } },
    },
});

export default responsiveFontSizes(theme);
