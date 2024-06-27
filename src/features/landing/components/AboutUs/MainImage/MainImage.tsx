import { Box } from '@mui/material';
import { ImageProps } from 'next/image';
// @ts-expect-error no types
import { getOptimizedImageProps } from 'next-export-optimize-images/image';
import React from 'react';
import { useTranslation } from 'react-i18next';

import breakpointsTheme from '@/components/UiBreakpoints';

import MainImageSrc from '../../../assets/img/about-vilna/desktop.jpg';
import PhoneMainImage from '../../../assets/img/about-vilna/mobile.jpg';
import TabletMainImage from '../../../assets/img/about-vilna/tablet.jpg';

import styles from './styles';

function MainImage(): React.ReactElement {
  const { t } = useTranslation();

  const mobileProps: ImageProps = getOptimizedImageProps({
    src: PhoneMainImage,
  }).props;
  const tabletProps: ImageProps = getOptimizedImageProps({
    src: TabletMainImage,
  }).props;
  const desktopProps: ImageProps = getOptimizedImageProps({
    src: MainImageSrc,
  }).props;

  return (
    <Box sx={styles.mainImageWrapper}>
      <picture>
        <source
          srcSet={mobileProps.src as string}
          width={mobileProps.width}
          height={mobileProps.height}
          media={`(max-width: ${breakpointsTheme.breakpoints.values.sm}px)`}
        />
        <source
          srcSet={tabletProps.src as string}
          width={tabletProps.width}
          height={tabletProps.height}
          media={`(max-width: ${breakpointsTheme.breakpoints.values.lg}px)`}
        />
        <img
          src={desktopProps.src as string}
          width={desktopProps.width}
          height={desktopProps.height}
          alt={t('Main image')}
        />
      </picture>
    </Box>
  );
}

export default MainImage;
