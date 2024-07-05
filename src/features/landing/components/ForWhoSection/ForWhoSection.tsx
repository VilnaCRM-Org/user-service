import { Box, Container } from '@mui/material';
import { ImageProps } from 'next/image';
// @ts-expect-error no types
import { getOptimizedImageProps } from 'next-export-optimize-images/image';
import React from 'react';

import DesktopVectorIcon from '../../assets/img/about-vilna/FrameDesktop.png';
import TabletVectorIcon from '../../assets/img/about-vilna/FrameTablet.png';

import { Cards } from './Cards';
import MainTitle from './MainTitle/MainTitle';
import styles from './styles';

function ForWhoSection(): React.ReactElement {
  const tabletProps: ImageProps = getOptimizedImageProps({
    src: TabletVectorIcon,
  }).props;
  const desktopProps: ImageProps = getOptimizedImageProps({
    src: DesktopVectorIcon,
  }).props;

  return (
    <Box id="forWhoSection" component="section" sx={styles.wrapper}>
      <Container>
        <Box sx={styles.content}>
          <MainTitle />
          <Box sx={styles.lgCardsWrapper}>
            <Cards />
          </Box>
          <Box sx={styles.mainImage}>
            <picture>
              <source
                srcSet={tabletProps.src as string}
                width={tabletProps.width}
                height={tabletProps.height}
                media="(max-width: 1130.98px)"
              />
              <img
                src={desktopProps.src as string}
                width={desktopProps.width}
                height={desktopProps.height}
                alt="vector"
                loading="lazy"
              />
            </picture>
          </Box>
        </Box>
      </Container>
      <Box sx={styles.smCardsWrapper}>
        <Cards />
      </Box>
      <Box sx={styles.line} />
    </Box>
  );
}

export default ForWhoSection;
