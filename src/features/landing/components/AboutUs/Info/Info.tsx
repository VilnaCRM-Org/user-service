'use client';

import { Stack, Button, Typography } from '@mui/material';
import React from 'react';
import { useTranslation } from 'react-i18next';

function Info() {
  const { t } = useTranslation();
  return (
    <Stack maxWidth="701px">
      <Typography
        variant="h1"
        sx={{
          color: '#1A1C1E',
          fontFamily: 'Golos',
          fontSize: '56px',
          fontWeight: 'bold',
          textAlign: 'center',
        }}
      >
        {t('about_vilna.heading_main')}
      </Typography>
      <Typography
        variant="h4"
        sx={{
          mt: '16px',
          color: '#1A1C1E',
          fontFamily: 'Golos',
          fontSize: '18px',
          fontWeight: 'normal',
          textAlign: 'center',
          lineHeight: '30px',
        }}
      >
        {t('about_vilna.text_main')}
      </Typography>
      <Button
        variant="contained"
        sx={{
          mt: '39px',
          py: '16px',
          px: '24px',
          borderRadius: '57px',
          fontFamily: 'Golos',
          fontSize: '12px',
          fontWeight: '500',
          lineHeight: '18px',
          alignSelf: 'center',
        }}
      >
        {t('about_vilna.button_main')}
      </Button>
    </Stack>
  );
}

export default Info;
