import { Grid, Box } from '@mui/material';
import React, { useEffect, useState } from 'react';

import ForWhoMainBackgroundSvg from '@/features/landing/components/ForWhoSection/ForWhoMainBackgroundSvg/ForWhoMainBackgroundSvg';
import { useScreenSize } from '@/features/landing/hooks/useScreenSize/useScreenSize';

const styles = {
  container: {
    height: '100%',
    minHeight: '498px',
    width: '100%',
    maxWidth: '820px',
    maxHeight: '637px',
    position: 'relative',
  },
  mainBackgroundSvgBox: {
    height: '100%',
    width: '100%',
    maxWidth: '47.375rem', // 758px
    position: 'absolute',
    top: 0,
    right: '-70px',
    zIndex: 750,
    paddingTop: '58px',
    pointerEvents: 'none',
    userSelect: 'none',
  },
  mainBackgroundSvgBoxLaptopAndLower: {
    right: '-31px',
  },
  mainImageBox: {
    position: 'absolute',
    zIndex: 800,
    top: '-58px',
    right: '63px',
    height: '27.5rem', // 440px
    width: '39.1875rem', // 627px
    pointerEvents: 'none',
    userSelect: 'none',
    marginTop: '58px',
  },
  secondaryImageBox: {
    position: 'absolute',
    zIndex: 850,
    bottom: '32px',
    right: '34px',
    width: '15.9375rem', // 255px
    height: '23.125rem', // 370px
    pointerEvents: 'none',
    userSelect: 'none',
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
  const { isTablet, isMobile, isSmallest, isLaptop } = useScreenSize();
  const [mainImageBoxStylesForTablet, setMainImageBoxStylesForTablet] =
    useState<React.CSSProperties>({});
  const [mainImageBoxStylesForMobileOrLower, setMainImageBoxStylesForMobileOrLower] =
    useState<React.CSSProperties>({});
  const [secondaryImageBoxStylesForTablet, setSecondaryImageBoxStylesForTablet] =
    useState<React.CSSProperties>({});
  const [secondaryImageBoxStylesForMobileOrLower, setSecondaryImageBoxStylesForMobileOrLower] =
    useState<React.CSSProperties>({});
  const [mainBackgroundSvgBoxStylesForSmallest, setMainBackgroundSvgBoxStylesForSmallest] =
    useState<React.CSSProperties>({});

  const resetStylesForTabletAndMobileAndLower = () => {
    setMainImageBoxStylesForTablet({});
    setMainImageBoxStylesForMobileOrLower({});
    setSecondaryImageBoxStylesForTablet({});
    setSecondaryImageBoxStylesForMobileOrLower({});
    setMainBackgroundSvgBoxStylesForSmallest({});
  };

  useEffect(() => {
    resetStylesForTabletAndMobileAndLower();

    if (isTablet) {
      setMainImageBoxStylesForTablet({
        right: '63px',
        top: '-79px',
        maxWidth: '505px',
      });

      setSecondaryImageBoxStylesForTablet({
        right: '34px',
        top: '148px',
        maxWidth: '255px',
      });
    }

    if (isMobile || isSmallest) {
      setMainImageBoxStylesForMobileOrLower({
        right: '0',
        top: '66px',
        maxWidth: '294px',
      });

      setSecondaryImageBoxStylesForMobileOrLower({
        right: '0',
        bottom: '66px',
        maxWidth: '148px',
      });
    }

    if (isSmallest) {
      setMainBackgroundSvgBoxStylesForSmallest({
        minWidth: '100vw',
        paddingTop: '58px',
      });
    }
  }, [isTablet, isMobile, isSmallest]);

  return (
    <Grid item xs={12} md={6} sx={{ ...styles.container }}>
      {/* Main Background SVG */}
      <Box
        sx={{
          ...styles.mainBackgroundSvgBox,
          ...(isLaptop || isTablet || isMobile ? styles.mainBackgroundSvgBoxLaptopAndLower : {}),
          ...mainBackgroundSvgBoxStylesForSmallest,
        }}
      >
        <ForWhoMainBackgroundSvg style={{ width: '100%' }}/>
      </Box>

      {/*  Main Image Box */}
      <Box
        sx={{
          ...styles.mainImageBox,
          ...mainImageBoxStylesForTablet,
          ...mainImageBoxStylesForMobileOrLower,
        }}
      >
        <img
          src={mainImageSrc}
          alt={mainImageTitle}
          style={{ maxWidth: '100%', width: '100%', objectFit: 'cover' }}
        />
      </Box>

      {/*  Secondary Image Box */}
      <Box
        sx={{
          ...styles.secondaryImageBox,
          ...secondaryImageBoxStylesForTablet,
          ...secondaryImageBoxStylesForMobileOrLower,
        }}
      >
        <img
          src={secondaryImageSrc}
          alt={secondaryImageTitle}
          style={{ maxWidth: '100%', width: '100%', objectFit: 'cover' }}
        />
      </Box>
    </Grid>
  );
}
