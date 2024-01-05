import { Stack } from '@mui/material';
import React from 'react';
import { useTranslation } from 'react-i18next';

import { UiTypography } from '@/components/ui';

function WhyUsText() {
  const { t } = useTranslation();

  return (
    <Stack maxWidth="39.5rem" spacing={2}>
      <UiTypography variant="h2">{t('why_we.heading')}</UiTypography>
      <UiTypography variant="bodyText18">{t('why_we.subtitle')}</UiTypography>
    </Stack>
  );
}

export default WhyUsText;
