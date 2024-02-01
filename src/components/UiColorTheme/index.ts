/* eslint-disable import/prefer-default-export */
import { Theme, createTheme } from '@mui/material';

export const colorTheme: Theme = createTheme({
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
  },
});
