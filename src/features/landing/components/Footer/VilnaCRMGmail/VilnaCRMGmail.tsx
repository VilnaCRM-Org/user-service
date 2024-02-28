import { Box } from '@mui/material';
import Link from 'next/link';
import React from 'react';
import { useTranslation } from 'react-i18next';

import { UiTypography } from '@/components/';

import styles from './styles';

function VilnaCRMGmail(): React.ReactElement {
  const { t } = useTranslation();

  return (
    <Box sx={styles.gmailWrapper}>
      <Link href="mailto:info@vilnacrm.com">
        <UiTypography variant="medium15" sx={styles.gmailText}>
          {t('footer.vilna_email')}
        </UiTypography>
      </Link>
    </Box>
  );
}

export default VilnaCRMGmail;
