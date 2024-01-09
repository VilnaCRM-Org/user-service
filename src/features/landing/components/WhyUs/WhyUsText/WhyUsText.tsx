import { Stack } from '@mui/material';
import React from 'react';
import { useTranslation } from 'react-i18next';

import { UiTypography } from '@/components/ui';

import { whyUsTextStyles } from './styles';

function WhyUsText() {
  const { t } = useTranslation();

  return (
    <Stack maxWidth="39.5rem">
      <UiTypography variant="h2" sx={whyUsTextStyles.title}>
        {t('why_we.heading')}
      </UiTypography>
      <UiTypography variant="bodyText18" sx={whyUsTextStyles.text}>
        {t('why_we.subtitle')}
      </UiTypography>
    </Stack>
  );
}

export default WhyUsText;
