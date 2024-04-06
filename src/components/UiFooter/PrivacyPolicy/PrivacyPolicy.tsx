import { Link, Stack } from '@mui/material';
import React from 'react';
import { useTranslation } from 'react-i18next';

import { UiTypography } from '@/components/';

import styles from './styles';

function PrivacyPolicy(): React.ReactElement {
  const { t } = useTranslation();

  return (
    <Stack direction="row" alignItems="center" sx={styles.wrapper}>
      <UiTypography variant="medium16" sx={styles.textColor}>
        <Link
          sx={styles.privacy}
          href="https://github.com/VilnaCRM-Org/website/blob/main/README.md"
        >
          {t('footer.privacy')}
        </Link>
      </UiTypography>
      <UiTypography variant="medium16" sx={styles.textColor}>
        <Link
          sx={styles.usage_policy}
          href="https://github.com/VilnaCRM-Org/website/blob/main/README.md"
        >
          {t('footer.usage_policy')}
        </Link>
      </UiTypography>
    </Stack>
  );
}

export default PrivacyPolicy;
