import { Stack } from '@mui/material';
import React from 'react';
import { Trans, useTranslation } from 'react-i18next';

import { MediumContainedBtn } from '@/components/UiButton';
import { DefaultTypography } from '@/components/UiTypography';

import styles from './styles';

function TextInfo(): React.ReactElement {
  const { t } = useTranslation();
  return (
    <Stack sx={styles.textWrapper}>
      <DefaultTypography variant="h1" sx={styles.title}>
        <Trans i18nKey="about_vilna.heading_main" />
      </DefaultTypography>
      <DefaultTypography variant="bodyText18" sx={styles.text}>
        {t('about_vilna.text_main')}
      </DefaultTypography>
      <MediumContainedBtn href="#signUp" sx={styles.button}>
        {t('about_vilna.button_main')}
      </MediumContainedBtn>
    </Stack>
  );
}

export default TextInfo;
