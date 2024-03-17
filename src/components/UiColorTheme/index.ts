import { Theme, createTheme } from '@mui/material';

const colorTheme: Theme = createTheme({
  palette: {
    primary: {
      main: '#1EAEFF',
    },
    secondary: {
      main: '#FFC01E',
    },
    error: {
      main: '#DC3939',
    },
    white: {
      main: '#FFF',
    },
    darkPrimary: {
      main: '#1A1C1E',
    },
    darkSecondary: {
      main: '#1B2327',
    },
    brandGray: {
      main: '#E1E7EA',
    },
    grey200: {
      main: '#404142',
    },
    grey250: {
      main: '#57595B',
    },
    grey300: {
      main: '#969B9D',
    },
    grey400: {
      main: '#D0D4D8',
    },
    grey500: {
      main: '#EAECEE',
    },
    backgroundGrey100: {
      main: '#FBFBFB',
    },
    backgroundGrey200: {
      main: '#f4f5f6',
    },
    backgroundGrey300: {
      main: '#F5F6F7',
    },
    containedButtonHover: {
      main: '#00A3FF',
    },
    containedButtonActive: {
      main: '#0399ED',
    },
    notchDeskBefore: {
      main: '#080805',
    },
    notchDeskAfter: {
      main: '#0e314c',
    },
    notchMobileBefore: {
      main: '#0c0b0e',
    },
    notchMobileAfter: {
      main: '#0f0b25',
    },
    textLinkHover: {
      main: '#297FFF',
    },
    textLinkActive: {
      main: '#0399ED',
    },
  },
});

export default colorTheme;
