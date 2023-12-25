'use client';

import { Stack, Typography } from '@mui/material';
import React from 'react';
import { useTranslation } from 'react-i18next';

import CustomButton from '@/components/ui/UIButton/UIButton';

function Info() {
  const { t } = useTranslation();
  return (
    <Stack maxWidth="43.813rem">
      <Typography
        variant="h1"
        sx={{
          color: '#1A1C1E',
          fontFamily: 'Golos',
          fontSize: '3.5rem',
          fontWeight: 'bold',
          textAlign: 'center',
        }}
      >
        {t('about_vilna.heading_main')}
      </Typography>
      <Typography
        variant="h4"
        sx={{
          mt: '1rem',
          color: '#1A1C1E',
          fontFamily: 'Golos',
          fontSize: '1.125rem',
          fontWeight: 'normal',
          textAlign: 'center',
          lineHeight: '1.875rem',
          mb: '39px',
        }}
      >
        {t('about_vilna.text_main')}
      </Typography>
      <CustomButton variant="contained" size="medium">
        {t('about_vilna.button_main')}
      </CustomButton>
    </Stack>
  );
}

export default Info;
