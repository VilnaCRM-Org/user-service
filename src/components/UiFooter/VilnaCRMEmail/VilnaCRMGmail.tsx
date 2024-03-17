import { Box, Link } from '@mui/material';
import React from 'react';

import { UiTypography } from '@/components/';

import styles from './styles';

function VilnaCRMEmail(): React.ReactElement {
  return (
    <Box sx={styles.emailWrapper}>
      <Link href="mailto:info@vilnacrm.com" sx={styles.emailLink}>
        <UiTypography variant="medium15" sx={styles.emailText}>
          {process.env.NEXT_PUBLIC_VILNACRM_GMAIL}
        </UiTypography>
      </Link>
    </Box>
  );
}

export default VilnaCRMEmail;
