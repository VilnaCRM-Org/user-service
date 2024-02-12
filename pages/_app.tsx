import { Theme, ThemeProvider, createTheme } from '@mui/material/styles';
import * as Sentry from '@sentry/react';
import React, { useEffect } from 'react';

import breakpointsTheme from '@/components/UiBreakpoints';
import { golos } from '@/config/Fonts';

import i18n from '../i18n';

import 'dotenv/config';
import '../styles/global.css';

Sentry.init({
  dsn: process.env.SENTRY_DSN_KEY,
  integrations: [
    new Sentry.BrowserTracing({
      tracePropagationTargets: [
        process.env.LOCALHOST || '',
        /^https:\/\/yourserver\.io\/api/,
      ],
    }),
    new Sentry.Replay(),
  ],
  tracesSampleRate: 1.0,
  replaysSessionSampleRate: 0.1,
  replaysOnErrorSampleRate: 1.0,
});

const theme: Theme = createTheme({
  breakpoints: breakpointsTheme.breakpoints,
  components: {
    MuiContainer: {
      styleOverrides: {
        root: {
          '@media (min-width: 23.438rem)': {
            padding: '0 2rem',
          },
          '@media (max-width: 26.563rem)': {
            padding: '0 0.9375rem',
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

function MyApp({
  Component,
}: {
  Component: React.ComponentType;
}): React.ReactElement {
  useEffect(() => {
    document.documentElement.dir = i18n.dir();
  }, []);

  return (
    <ThemeProvider theme={theme}>
      <main className={golos.className}>
        <Component />
      </main>
    </ThemeProvider>
  );
}

export default MyApp;
