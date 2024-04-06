import { Box, Link } from '@mui/material';
import React from 'react';

import { UiTypography } from '@/components/';

import styles from './styles';

function VilnaCRMEmail(): React.ReactElement {
  return (
    <Box sx={styles.emailWrapper}>
      <UiTypography variant="medium15" sx={styles.emailText}>
        <Link href="mailto:info@vilnacrm.com" sx={styles.emailLink}>
          {process.env.NEXT_PUBLIC_VILNACRM_GMAIL}
        </Link>
      </UiTypography>
    </Box>
  );
}

export default VilnaCRMEmail;
