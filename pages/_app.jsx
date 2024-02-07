/* eslint-disable import/extensions */
/* eslint-disable import/no-unresolved */
import '../styles/global.css';
import * as Sentry from '@sentry/react';
import React, { useEffect } from 'react';
import { createTheme, ThemeProvider } from '@mui/material/styles';
import * as PropTypes from 'prop-types';
import { ApolloProvider } from '@apollo/client';
import client from '@/features/landing/api/graphql/apollo';
import { golos } from '@/config/Fonts';
import i18n from '../i18n';
import 'dotenv/config';
import breakpointsTheme from '@/components/UiBreakpoints';

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

  useEffect(() => {
    document.documentElement.dir = i18n.dir();
  }, []);

  return (
    <ThemeProvider theme={theme}>
      <ApolloProvider client={client}>
        <main className={golos.className}>
          <Component />
        </main>
      </ApolloProvider>
    </ThemeProvider>
  );
}

MyApp.propTypes = {
  Component: PropTypes.elementType.isRequired,
};

export default MyApp;
