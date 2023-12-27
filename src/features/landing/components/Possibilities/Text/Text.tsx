'use client';

import { Stack } from '@mui/material';
import React from 'react';
import { useTranslation } from 'react-i18next';

import UITypography from '@/components/ui/UITypography/UITypography';

function Text() {
  const { t } = useTranslation();

  return (
    <Stack justifyContent="center" width="100%">
      <UITypography
        variant="h3"
        sx={{
          padding: '12px 32px',
          alignSelf: 'center',
          borderRadius: '1rem',
          backgroundColor: '#FFC01E',
        }}
      >
        {t('unlimited_possibilities.main_heading_text')}
      </UITypography>
      <UITypography variant="h2" sx={{ textAlign: 'center' }}>
        {t('unlimited_possibilities.secondary_heading_text')}
      </UITypography>
    </Stack>
  );
}

export default Text;
