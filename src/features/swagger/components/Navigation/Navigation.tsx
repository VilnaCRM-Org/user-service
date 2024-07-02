import { Box, Stack } from '@mui/material';
import Image from 'next-export-optimize-images/image';
import React from 'react';
import { useTranslation } from 'react-i18next';

import { UiTypography } from '@/components';

import Svg from '../../assets/svg/navigation/arrow.svg';

import styles from './styles';

function Navigation(): React.ReactElement {
  const { t } = useTranslation();

  return (
    <Box sx={styles.navigationWrapper}>
      <Stack direction="row" alignItems="center" role="navigation" sx={styles.navigationButton}>
        <Image src={Svg} />
        <UiTypography sx={styles.navigationText}>{t('To the main page')}</UiTypography>
      </Stack>
    </Box>
  );
}

export default Navigation;
