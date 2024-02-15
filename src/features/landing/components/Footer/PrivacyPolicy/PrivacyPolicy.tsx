import { Link, Stack } from '@mui/material';
import React from 'react';
import { useTranslation } from 'react-i18next';

import { DefaultTypography } from '@/components/UiTypography';

import styles from './styles';

function PrivacyPolicy(): React.ReactElement {
  const { t } = useTranslation();

  return (
    <Stack direction="row" alignItems="center" sx={styles.wrapper}>
      <Link sx={styles.privacy} href="/">
        <DefaultTypography variant="medium16" sx={styles.textColor}>
          {t('footer.privacy')}
        </DefaultTypography>
      </Link>
      <Link sx={styles.usage_policy} href="/">
        <DefaultTypography variant="medium16" sx={styles.textColor}>
          {t('footer.usage_policy')}
        </DefaultTypography>
      </Link>
    </Stack>
  );
}

export default PrivacyPolicy;
