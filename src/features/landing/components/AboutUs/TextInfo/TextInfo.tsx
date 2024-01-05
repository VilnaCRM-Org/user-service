import { Stack } from '@mui/material';
import React from 'react';
import { useTranslation } from 'react-i18next';

import { UiButton, UiTypography } from '@/components/ui';

function TextInfo() {
  const { t } = useTranslation();
  return (
    <Stack maxWidth="43.813rem" mb="50px">
      <UiTypography variant="h1" sx={{ textAlign: 'center' }}>
        {t('about_vilna.heading_main')}
      </UiTypography>
      <UiTypography
        variant="bodyText18"
        sx={{
          mt: '1rem',
          textAlign: 'center',
          mb: '39px',
        }}
      >
        {t('about_vilna.text_main')}
      </UiTypography>
      <UiButton variant="contained" size="medium">
        {t('about_vilna.button_main')}
      </UiButton>
    </Stack>
  );
}

export default TextInfo;
