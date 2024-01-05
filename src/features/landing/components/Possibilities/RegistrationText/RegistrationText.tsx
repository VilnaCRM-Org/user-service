import { Stack } from '@mui/material';
import React from 'react';
import { useTranslation } from 'react-i18next';

import { UiTypography } from '@/components/ui';

function RegistrationText() {
  const { t } = useTranslation();

  return (
    <Stack justifyContent="center" width="100%">
      <UiTypography
        variant="h3"
        sx={{
          padding: '12px 32px',
          alignSelf: 'center',
          borderRadius: '1rem',
          backgroundColor: '#FFC01E',
        }}
      >
        {t('unlimited_possibilities.main_heading_text')}
      </UiTypography>
      <UiTypography variant="h2" sx={{ textAlign: 'center' }}>
        {t('unlimited_possibilities.secondary_heading_text')}
      </UiTypography>
    </Stack>
  );
}

export default RegistrationText;
