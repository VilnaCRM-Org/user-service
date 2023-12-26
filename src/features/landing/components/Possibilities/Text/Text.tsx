'use client';

import { Box, Stack, Typography } from '@mui/material';
import React from 'react';
import { useTranslation } from 'react-i18next';

function Text() {
  const { t } = useTranslation();

  return (
    <Stack justifyContent="center" width="100%">
      <Box
        sx={{
          padding: '12px 32px',
          alignSelf: 'center',
          borderRadius: '1rem',
          fontFamily: 'Golos',
          fontSize: '2.25rem',
          fontWeight: '600',
          lineHeight: 'normal',
          backgroundColor: '#FFC01E',
        }}
      >
        {t('unlimited_possibilities.main_heading_text')}
      </Box>
      <Typography
        variant="h2"
        sx={{
          mt: '0.438rem',
          color: '#1A1C1E',
          fontFamily: 'Golos',
          fontSize: '2.875rem',
          fontWeight: 'bold',
          textAlign: 'center',
        }}
      >
        {t('unlimited_possibilities.secondary_heading_text')}
      </Typography>
    </Stack>
  );
}

export default Text;
