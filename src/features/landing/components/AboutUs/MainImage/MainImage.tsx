import { Box } from '@mui/material';
import React from 'react';
import { useTranslation } from 'react-i18next';

import breakpointsTheme from '@/components/UiBreakpoints';

import MainImageSrc from '../../../assets/img/about-vilna/desktop.jpg';
import PhoneMainImage from '../../../assets/img/about-vilna/mobile.jpg';
import TabletMainImage from '../../../assets/img/about-vilna/tablet.jpg';

import styles from './styles';

function MainImage(): React.ReactElement {
  const { t } = useTranslation();

  return (
    <Box sx={styles.mainImageWrapper}>
      <img
        src={MainImageSrc}
        srcSet={`${PhoneMainImage.src} 539w, ${TabletMainImage.src} 922w, ${MainImageSrc.src} 1280w`}
        sizes={`
          (max-width: ${breakpointsTheme.breakpoints.values.sm}px) 530px,
          (max-width: ${breakpointsTheme.breakpoints.values.lg}px) 920px,
          1270px`}
        alt={t('Main image')}
        width={766}
        height={498}
      />
    </Box>
  );
}

export default MainImage;
