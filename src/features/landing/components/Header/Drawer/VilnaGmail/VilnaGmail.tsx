import { Stack } from '@mui/material';
import Link from 'next/link';
import React from 'react';
import { useTranslation } from 'react-i18next';

import { UiTypography } from '@/components';
import colorTheme from '@/components/UiColorTheme';

import styles from './styles';

function VilnaGmail(): React.ReactElement {
  const { t } = useTranslation();
  return (
    <Stack sx={styles.gmailWrapper} justifyContent="center">
      <Link href="mailto:info@vilnacrm.com">
        <Stack
          justifyContent="center"
          alignItems="center"
          gap="0.62rem"
          flexDirection="row"
        >
          <UiTypography sx={styles.at}>@</UiTypography>
          <UiTypography
            variant="demi18"
            color={colorTheme.palette.darkSecondary.main}
          >
            {t('header.drawer.vilna_email')}
          </UiTypography>
        </Stack>
      </Link>
    </Stack>
  );
}

export default VilnaGmail;
