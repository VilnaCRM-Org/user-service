import { Stack } from '@mui/material';
import React from 'react';
import { Trans, useTranslation } from 'react-i18next';

import { UiButton, UiTypography } from '@/components/ui';

import { textInfoStyles } from './styles';

function TextInfo() {
  const { t } = useTranslation();
  return (
    <Stack maxWidth="43.813rem" mb="50px">
      <UiTypography variant="h1" sx={textInfoStyles.title}>
        <Trans i18nKey="about_vilna.title_main">
          Перша українська CRM <br /> з відкритим кодом
        </Trans>
      </UiTypography>
      <UiTypography variant="bodyText18" sx={textInfoStyles.text}>
        {t('about_vilna.text_main')}
      </UiTypography>
      <UiButton variant="contained" size="medium" sx={textInfoStyles.button}>
        {t('about_vilna.button_main')}
      </UiButton>
    </Stack>
  );
}

export default TextInfo;
