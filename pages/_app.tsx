import { ThemeProvider } from '@mui/material/styles';
import * as Sentry from '@sentry/react';
import React, { useEffect } from 'react';

import { theme } from '@/components/AppTheme';
import { golos } from '@/config/Fonts';

import i18n from '../i18n';

import '../styles/global.css';

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
