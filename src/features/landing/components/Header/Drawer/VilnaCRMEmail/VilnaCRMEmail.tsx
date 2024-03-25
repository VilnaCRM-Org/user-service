import { Stack } from '@mui/material';
import Link from 'next/link';
import React from 'react';

import { UiTypography } from '@/components/';

import styles from './styles';
import 'dotenv/config';

function VilnaCRMEmail(): React.ReactElement {
  return (
    <Stack sx={styles.emailWrapper} justifyContent="center">
      <Link href="mailto:info@vilnacrm.com">
        <Stack
          justifyContent="center"
          alignItems="center"
          gap="0.62rem"
          flexDirection="row"
        >
          <UiTypography sx={styles.at}>@</UiTypography>
          <UiTypography variant="demi18" sx={styles.emailText}>
            {process.env.NEXT_PUBLIC_VILNACRM_GMAIL}
          </UiTypography>
        </Stack>
      </Link>
    </Stack>
  );
}

export default VilnaCRMEmail;
