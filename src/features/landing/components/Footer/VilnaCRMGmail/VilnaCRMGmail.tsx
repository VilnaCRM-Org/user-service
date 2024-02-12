import { Box } from '@mui/material';
import React from 'react';
import { useTranslation } from 'react-i18next';

import { UiTypography } from '@/components';

import styles from './styles';

function VilnaCRMGmail(): React.ReactElement {
  const { t } = useTranslation();

  return (
    <Box sx={styles.gmailWrapper}>
      <UiTypography variant="medium15" sx={styles.gmailText}>
        {t('footer.vilna_email')}
      </UiTypography>
    </Box>
  );
}

export default VilnaCRMGmail;
