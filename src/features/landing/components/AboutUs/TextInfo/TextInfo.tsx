import { Link, Stack } from '@mui/material';
import React from 'react';
import { Trans, useTranslation } from 'react-i18next';

import { UiButton, UiTypography } from '@/components';

import styles from './styles';

function TextInfo(): React.ReactElement {
  const { t } = useTranslation();

  return (
    <Stack sx={styles.textWrapper}>
      <UiTypography component="h1" variant="h1" sx={styles.title}>
        <Trans i18nKey="about_vilna.heading_main" />
      </UiTypography>
      <UiTypography variant="bodyText18" sx={styles.text}>
        {t('about_vilna.text_main')}
      </UiTypography>
      <Link href="#signUp" sx={styles.link}>
        <UiButton
          sx={styles.button as React.CSSProperties}
          variant="contained"
          size="medium"
        >
          {t('about_vilna.button_main')}
        </UiButton>
      </Link>
    </Stack>
  );
}

export default TextInfo;
