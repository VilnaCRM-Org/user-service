import { Stack } from '@mui/material';
import React from 'react';
import { Trans, useTranslation } from 'react-i18next';

import { UiTypography } from '@/components';

import { headingStyles } from './styles';

function Heading() {
  const { t } = useTranslation();

  return (
    <Stack maxWidth="39.5rem">
      <UiTypography variant="h2" sx={headingStyles.title}>
        {t('why_we.heading')}
      </UiTypography>
      <UiTypography variant="bodyText18" sx={headingStyles.text}>
        <Trans i18nKey="why_we.subtitle" />
      </UiTypography>
    </Stack>
  );
}

export default Heading;
