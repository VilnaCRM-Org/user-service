import { Box } from '@mui/material';
import React from 'react';
import { useTranslation } from 'react-i18next';

import { DefaultTypography } from '@/components/UiTypography';

import styles from './styles';

function VilnaCRMGmail(): React.ReactElement {
  const { t } = useTranslation();

  return (
    <Box sx={styles.gmailWrapper}>
      <DefaultTypography variant="medium15" sx={styles.gmailText}>
        {t('footer.vilna_email')}
      </DefaultTypography>
    </Box>
  );
}

export default VilnaCRMGmail;
