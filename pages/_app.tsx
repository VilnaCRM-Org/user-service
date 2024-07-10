import { ApolloProvider } from '@apollo/client';
import { ThemeProvider } from '@mui/material/styles';
import { GoogleAnalytics } from '@next/third-parties/google';
import * as Sentry from '@sentry/react';
import React, { useEffect } from 'react';

import { theme } from '@/components/AppTheme';
import { golos } from '@/config/Fonts/golos';

import 'swagger-ui-react/swagger-ui.css';

import '../styles/global.css';

import '../styles/swagger/styles.scss';

import i18n from '../i18n';
import client from '../src/features/landing/api/graphql/apollo';

Sentry.init({
  dsn: process.env.SENTRY_DSN_KEY,
  integrations: [
    new Sentry.BrowserTracing({
      tracePropagationTargets: [
        process.env.NEXT_PUBLIC_LOCALHOST || '',
        process.env.NEXT_PUBLIC_API_URL || '',
      ],
    }),
    new Sentry.Replay(),
  ],
  tracesSampleRate: 1.0,
  replaysSessionSampleRate: 0.1,
  replaysOnErrorSampleRate: 1.0,
});

function MyApp({ Component }: { Component: React.ComponentType }): React.ReactElement {
  useEffect(() => {
    document.documentElement.dir = i18n.dir();
  }, []);

  return (
    <ThemeProvider theme={theme}>
      <ApolloProvider client={client}>
        <main className={golos.className}>
          <Component />
          <GoogleAnalytics gaId="G-XYZ" />
        </main>
      </ApolloProvider>
    </ThemeProvider>
  );
}

export default MyApp;
