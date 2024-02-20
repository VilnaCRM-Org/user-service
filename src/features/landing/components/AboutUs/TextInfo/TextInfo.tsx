import { Stack } from '@mui/material';
import Link from 'next/link';
import React from 'react';
import { Trans, useTranslation } from 'react-i18next';

import { UiButton, UiTypography } from '@/components';

import styles from './styles';

function TextInfo(): React.ReactElement {
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
        sx={styles.button as React.CSSProperties}
        variant="contained"
        size="medium"
      >
        <Link href="#signUp">{t('about_vilna.button_main')}</Link>
      </UiButton>
    </Stack>
  );
}

export default TextInfo;
