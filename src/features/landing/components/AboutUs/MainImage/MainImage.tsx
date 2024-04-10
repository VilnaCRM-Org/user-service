import { Box } from '@mui/material';
import Image from 'next/image';
import React from 'react';
import { useTranslation } from 'react-i18next';

import MainImageSrc from '../../../assets/img/about-vilna/desktop.jpg';

import styles from './styles';

function MainImage(): React.ReactElement {
  const { t } = useTranslation();

  return (
    <Box sx={styles.mainImageWrapper} data-testid="main-image">
      <Image
        src={MainImageSrc}
        alt={t('Main image')}
        width={766}
        height={498}
      />
    </Box>
  );
}
export default MainImage;
