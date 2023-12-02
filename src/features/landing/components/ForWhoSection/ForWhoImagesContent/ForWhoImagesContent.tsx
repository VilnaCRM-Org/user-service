import { Grid, Box } from '@mui/material';
import Image from 'next/image';
import React from 'react';

import useScreenSize from '../../../hooks/useScreenSize/useScreenSize';
import ForWhoMainBackgroundSvg from '../ForWhoMainBackgroundSvg/ForWhoMainBackgroundSvg';

const styles = {
  container: {
    height: '100%',
    width: '100%',
    maxHeight: '39.8125rem', // 637px
    maxWidth: '51.25rem', // 820px
    position: 'relative',
    top: '58px',
  },
  mainBackgroundSvgBox: {
    height: '36.12rem', // 577.92px
    width: '53.8125rem', // 861px
    position: 'absolute',
    top: '-58px',
    right: '-4.375rem', // -70px
    zIndex: 750,
    pointerEvents: 'none',
    userSelect: 'none',
  },
  mainBackgroundSvgBoxLaptopAndLower: {
    right: '-31px',
  },
  mainImageBox: {
    position: 'absolute',
    zIndex: 800,
    top: '0',
    right: '63px',
    height: '100%',
    width: '100%',
    maxHeight: '27.5rem', // 440px
    maxWidth: '39.1875rem', // 627px
    pointerEvents: 'none',
    userSelect: 'none',
  },
  mainImage: {
    width: 'auto',
    height: 'auto',
    maxWidth: '100%',
    objectFit: 'cover',
  },
  secondaryImageBox: {
    position: 'absolute',
    zIndex: 850,
    top: '11.1875rem', // 179px
    right: '34px',
    width: '15.9375rem', // 255px
    height: '23.125rem', // 370px
    pointerEvents: 'none',
    userSelect: 'none',
  },
  secondaryImage: {
    width: 'auto',
    height: 'auto',
    maxWidth: '100%',
    objectFit: 'cover',
  },
};

export default function ForWhoImagesContent({
  mainImageSrc,
  mainImageTitle,
  secondaryImageSrc,
  secondaryImageTitle,
}: {
  mainImageSrc: string;
  mainImageTitle: string;
  secondaryImageSrc: string;
  secondaryImageTitle: string;
}) {
  const { isTablet, isMobile, isLaptop } = useScreenSize();

  return (
    <Grid item xs={12} md={8} sx={{ ...styles.container }}>
      {/* Main Background SVG */}
      <Box
        sx={{
          ...styles.mainBackgroundSvgBox,
          ...(isLaptop || isTablet || isMobile ? styles.mainBackgroundSvgBoxLaptopAndLower : {}),
        }}
      >
        <ForWhoMainBackgroundSvg style={{ width: '53.8125rem', height: '36.12rem' }} />
      </Box>

      {/*  Main Image Box */}
      <Box
        sx={{
          ...styles.mainImageBox,
        }}
      >
        <Image
          width={1398}
          height={1024}
          src={mainImageSrc}
          alt={mainImageTitle}
          style={{ ...styles.mainImage, objectFit: 'cover' }}
        />
      </Box>

      {/*  Secondary Image Box */}
      <Box
        sx={{
          ...styles.secondaryImageBox,
        }}
      >
        <Image
          width={654}
          height={868}
          src={secondaryImageSrc}
          alt={secondaryImageTitle}
          style={{ ...styles.secondaryImage, objectFit: 'cover' }}
        />
      </Box>
    </Grid>
  );
}
