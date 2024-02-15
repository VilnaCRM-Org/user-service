import { Stack } from '@mui/material';
import Link from 'next/link';
import React from 'react';
import { useTranslation } from 'react-i18next';

import { DefaultTypography } from '@/components/UiTypography';

import styles from './styles';

function VilnaCRMGmail(): React.ReactElement {
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
          <DefaultTypography sx={styles.at}>@</DefaultTypography>
          <DefaultTypography variant="demi18" sx={styles.gmailText}>
            {t('header.drawer.vilna_email')}
          </DefaultTypography>
        </Stack>
      </Link>
    </Stack>
  );
}

export default VilnaCRMGmail;
