'use client';

import { Button, Stack, Typography } from '@mui/material';
import React from 'react';
import { useTranslation } from 'react-i18next';

function Text() {
  const { t } = useTranslation();

  return (
    <Stack sx={{ width: '100%', justifyContent: 'center' }}>
      <Button
        variant="contained"
        color="secondary"
        sx={{
          py: '12px',
          px: '32px',
          alignSelf: 'center',
          borderRadius: '16px',
          fontFamily: 'Golos',
          fontSize: '36px',
          fontWeight: '600',
          lineHeight: 'normal',
        }}
      >
        {t('unlimited_possibilities.main_heading_text')}
      </Button>
      <Typography
        variant="h2"
        sx={{
          mt: '7px',
          color: '#1A1C1E',
          fontFamily: 'Golos',
          fontSize: '46px',
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
