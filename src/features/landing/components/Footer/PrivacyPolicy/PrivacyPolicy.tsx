import { Link, Stack } from '@mui/material';
import React from 'react';
import { useTranslation } from 'react-i18next';

import { UiTypography } from '@/components';
import colorTheme from '@/components/UiColorTheme';

import styles from './styles';

function PrivacyPolicy(): React.ReactElement {
  const { t } = useTranslation();

  return (
    <Stack direction="row" alignItems="center" sx={styles.wrapper}>
      <Link sx={styles.privacy} href="/">
        <UiTypography
          variant="medium16"
          color={colorTheme.palette.grey300.main}
        >
          {t('footer.privacy')}
        </UiTypography>
      </Link>
      <Link sx={styles.usage_policy} href="/">
        <UiTypography
          variant="medium16"
          color={colorTheme.palette.grey300.main}
        >
          {t('footer.usage_policy')}
        </UiTypography>
      </Link>
    </Stack>
  );
}

export default PrivacyPolicy;
