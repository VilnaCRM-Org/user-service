'use client';

import { Stack, Typography } from '@mui/material';
import React from 'react';
import { useTranslation } from 'react-i18next';

function Text() {
  const { t } = useTranslation();

  return (
    <Stack maxWidth="39.5rem" spacing={2}>
      <Typography
        variant="h1"
        sx={{
          color: '#1A1C1E',
          fontFamily: 'Golos',
          fontSize: '2.875rem',
          fontWeight: 'bold',
        }}
      >
        {t('why_we.heading')}
      </Typography>
      <Typography
        variant="h4"
        sx={{
          color: '#1A1C1E',
          fontFamily: 'Golos',
          fontSize: '1.125rem',
          fontWeight: 'normal',
          lineHeight: '1.875rem',
        }}
      >
        {t('why_we.subtitle')}
      </Typography>
    </Stack>
  );
}

export default Text;
