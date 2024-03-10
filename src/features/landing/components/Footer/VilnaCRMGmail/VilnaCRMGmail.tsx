import { Box } from '@mui/material';
import Link from 'next/link';
import React from 'react';

import { UiTypography } from '@/components/';

import styles from './styles';

function VilnaCRMGmail(): React.ReactElement {
  return (
    <Box sx={styles.gmailWrapper}>
      <Link href="mailto:info@vilnacrm.com">
        <UiTypography variant="medium15" sx={styles.gmailText}>
          {process.env.NEXT_PUBLIC_VILNACRM_GMAIL}
        </UiTypography>
      </Link>
    </Box>
  );
}

export default VilnaCRMGmail;
