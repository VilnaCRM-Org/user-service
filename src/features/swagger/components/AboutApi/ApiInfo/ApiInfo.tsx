import { Box, Stack } from '@mui/material';
import React from 'react';

import { UiTypography } from '@/components';

// import { ApiDot } from '../ApiDot';

import styles from './styles';

function ApiInfo(): React.ReactElement {
  return (
    <Box sx={styles.apiInfoWrapper}>
      <Stack direction="row" alignItems="center" gap="1.5rem" sx={styles.apiHeader}>
        <UiTypography component="h2" variant="h2" sx={styles.apiTitle}>
          User Service API
        </UiTypography>
        <UiTypography sx={styles.apiVersion}>1.0.0</UiTypography>
      </Stack>
      <UiTypography component="p" variant="bodyText18" sx={styles.description}>
        This API provides endpoints to manage user data within a VilnaCRM.
      </UiTypography>
    </Box>
  );
}

export default ApiInfo;
