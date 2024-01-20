import '../styles/global.css';
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
      tracePropagationTargets: [
        process.env.LOCALHOST,
        /^https:\/\/yourserver\.io\/api/,
      ],
    }),
    new Sentry.Replay(),
  ],
  tracesSampleRate: 1.0,
  replaysSessionSampleRate: 0.1,
  replaysOnErrorSampleRate: 1.0,
});

function MyApp({ Component }) {
  const theme = createTheme({
    breakpoints: {
      values: { xs: 375.98, sm: 640.98, md: 767.98, lg: 1023.98, xl: 1439.98 },
    },
    typography: {
      fontFamily: 'Golos, Inter',
    },
    components: {
      MuiContainer: {
        styleOverrides: {
          root: {
            '@media (min-width: 23.438rem)': {
              padding: '0 2rem',
            },
            '@media (max-width: 425px)': {
              padding: '0 15px',
            },
            '@media (min-width: 64rem)': {
              width: '100%',
              maxWidth: '78.375rem',
              paddingLeft: '2rem',
              paddingRight: '2rem',
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
