'use client';

import { Stack } from '@mui/material';
import React from 'react';
import { useTranslation } from 'react-i18next';

import UITypography from '@/components/ui/UITypography/UITypography';

function Text() {
  const { t } = useTranslation();

  return (
    <Stack maxWidth="39.5rem" spacing={2}>
      <UITypography variant="h2">{t('why_we.heading')}</UITypography>
      <UITypography variant="bodyText18">{t('why_we.subtitle')}</UITypography>
    </Stack>
  );
}

export default Text;
