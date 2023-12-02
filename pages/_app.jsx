import '../styles/globals.css';
import * as Sentry from '@sentry/react';
import React, { useEffect } from 'react';
import { createTheme, ThemeProvider } from '@mui/material/styles';
import * as PropTypes from 'prop-types';

import i18n from '../i18n';
import 'dotenv/config';

Sentry.init({
  dsn: process.env.SENTRY_DSN_KEY,
  integrations: [
    new Sentry.BrowserTracing({
      tracePropagationTargets: [process.env.LOCALHOST, /^https:\/\/yourserver\.io\/api/],
    }),
    new Sentry.Replay(),
  ],
  tracesSampleRate: 1.0,
  replaysSessionSampleRate: 0.1,
  replaysOnErrorSampleRate: 1.0,
});

const customBreakpoints = {
  xs: 0,
  sm: 375,
  md: 640,
  smallTablet: 850,
  lg: 1024,
  xl: 1440,
};

// eslint-disable-next-line react/prop-types
function MyApp({ Component }) {
  const theme = createTheme({
    palette: {
      mode: 'light',
    },
    breakpoints: {
      values: customBreakpoints,
    },
    typography: {},
    components: {
      MuiContainer: {
        styleOverrides: {
          root: {
            '@media (min-width: 375px)': {
              paddingLeft: '0px',
              paddingRight: '0px',
            },
            '@media (min-width: 1024px)': {
              width: '100%',
              maxWidth: '1192px',
            },
          },
        },
      },
    },
  });

  useEffect(() => {
    document.documentElement.dir = i18n.dir();
  }, []);

  return (
    <ThemeProvider theme={theme}>
      <Component />
    </ThemeProvider>
  );
}

MyApp.propTypes = {
  Component: PropTypes.elementType.isRequired,
};

export default MyApp;
