import { Stack } from '@mui/material';
import React from 'react';
import { Trans, useTranslation } from 'react-i18next';

import { DefaultTypography } from '@/components/UiTypography';

import styles from './styles';

function Heading(): React.ReactElement {
  const { t } = useTranslation();

  return (
    <Stack maxWidth="39.5rem">
      <DefaultTypography variant="h2" sx={styles.title}>
        {t('why_us.heading')}
      </DefaultTypography>
      <DefaultTypography variant="bodyText18" sx={styles.text}>
        <Trans i18nKey="why_us.subtitle" />
      </DefaultTypography>
    </Stack>
  );
}

export default Heading;
