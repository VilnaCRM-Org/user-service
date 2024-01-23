import { Stack } from '@mui/material';
import React from 'react';
import { Trans, useTranslation } from 'react-i18next';

import { UiButton, UiTypography } from '@/components';

import { textInfoStyles } from './styles';

function TextInfo() {
  const { t } = useTranslation();
  return (
    <Stack sx={textInfoStyles.textWrapper}>
      <UiTypography variant="h1" sx={textInfoStyles.title}>
        <Trans i18nKey="about_vilna.heading_main" />
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
