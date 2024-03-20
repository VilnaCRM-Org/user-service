import { Box, Link } from '@mui/material';
import React from 'react';
import { Trans, useTranslation } from 'react-i18next';

import { UiButton, UiTypography } from '@/components';

import styles from './styles';

function MainTitle(): React.ReactElement {
  const { t } = useTranslation();

  return (
    <Box>
      <UiTypography variant="h2" sx={styles.title}>
        {t('for_who.heading_main')}
      </UiTypography>
      <UiTypography sx={styles.description} variant="bodyText18">
        <Trans i18nKey="for_who.text_main" />
      </UiTypography>
      <Link
        href="#signUp"
        aria-label={t('for_who.aria_label')}
        data-testid="for-who-sign-up"
      >
        <UiButton sx={styles.button} variant="contained" size="medium">
          {t('for_who.button_text')}
        </UiButton>
      </Link>
    </Box>
  );
}

export default MainTitle;
