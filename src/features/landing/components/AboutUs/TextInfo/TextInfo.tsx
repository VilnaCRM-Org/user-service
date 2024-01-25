import { Stack } from '@mui/material';
import React from 'react';
import { Trans, useTranslation } from 'react-i18next';

import { UiButton, UiTypography } from '@/components';

import styles from './styles';

function TextInfo() {
  const { t } = useTranslation();
  return (
    <Stack sx={styles.textWrapper}>
      <UiTypography variant="h1" sx={styles.title}>
        <Trans i18nKey="about_vilna.heading_main" />
      </UiTypography>
      <UiTypography variant="bodyText18" sx={styles.text}>
        {t('about_vilna.text_main')}
      </UiTypography>
      <UiButton
        variant="contained"
        size="medium"
        href="#signUp"
        sx={styles.button}
      >
        {t('about_vilna.button_main')}
      </UiButton>
    </Stack>
  );
}

export default TextInfo;
