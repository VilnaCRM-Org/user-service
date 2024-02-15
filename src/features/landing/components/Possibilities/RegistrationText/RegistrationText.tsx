import { Stack } from '@mui/material';
import React from 'react';
import { useTranslation } from 'react-i18next';

import { DefaultTypography } from '@/components/UiTypography';

import styles from './styles';

function RegistrationText(): React.ReactElement {
  const { t } = useTranslation();

  return (
    <Stack justifyContent="center" width="100%" sx={styles.textWrapper}>
      <DefaultTypography variant="h3" sx={styles.title}>
        {t('unlimited_possibilities.main_heading_text')}
      </DefaultTypography>
      <DefaultTypography variant="h2" sx={styles.text}>
        {t('unlimited_possibilities.secondary_heading_text')}
      </DefaultTypography>
    </Stack>
  );
}

export default RegistrationText;
