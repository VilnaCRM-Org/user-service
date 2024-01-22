import { Stack } from '@mui/material';
import React from 'react';
import { useTranslation } from 'react-i18next';

import { UiTypography } from '@/components';

import { registrationTextStyles } from './styles';

function RegistrationText() {
  const { t } = useTranslation();

  return (
    <Stack
      justifyContent="center"
      width="100%"
      sx={registrationTextStyles.textWrapper}
    >
      <UiTypography variant="h3" sx={registrationTextStyles.title}>
        {t('unlimited_possibilities.main_heading_text')}
      </UiTypography>
      <UiTypography variant="h2" sx={registrationTextStyles.text}>
        {t('unlimited_possibilities.secondary_heading_text')}
      </UiTypography>
    </Stack>
  );
}

export default RegistrationText;
