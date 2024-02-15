import { Box } from '@mui/material';
import React from 'react';
import { Trans, useTranslation } from 'react-i18next';

import { MediumContainedBtn } from '@/components/UiButton';
import { DefaultTypography } from '@/components/UiTypography';

import styles from './styles';

function MainTitle(): React.ReactElement {
  const { t } = useTranslation();
  return (
    <Box>
      <DefaultTypography variant="h2" sx={styles.title}>
        {t('for_who.heading_main')}
      </DefaultTypography>
      <DefaultTypography sx={styles.description} variant="bodyText18">
        <Trans i18nKey="for_who.text_main" />
      </DefaultTypography>
      <MediumContainedBtn sx={styles.button} href="#signUp">
        {t('for_who.button_text')}
      </MediumContainedBtn>
    </Box>
  );
}

export default MainTitle;
