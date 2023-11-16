import React, { useEffect, useState } from 'react';
import { Grid, Box } from '@mui/material';
import {
  ForWhoMainBackgroundSvg,
} from '@/features/landing/components/ForWhoSection/ForWhoMainBackgroundSvg/ForWhoMainBackgroundSvg';
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
    position: 'absolute',
    top: 0,
    right: 0,
    zIndex: 750,
    paddingTop: '58px',
  },
  mainImageBox: {
    position: 'absolute',
    zIndex: 800,
    top: '-58px',
    right: '63px',
    maxWidth: '627px',
    width: '100%',
  },
  secondaryImageBox: {
    position: 'absolute',
    zIndex: 850,
    bottom: '32px',
    right: '34px',
    maxWidth: '255px',
    width: '100%',
  },
};

export function ForWhoImagesContent({
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
  const { isTablet, isMobile, isSmallest } = useScreenSize();
  const [mainImageBoxStylesForTablet, setMainImageBoxStylesForTablet] = useState<React.CSSProperties>({});
  const [mainImageBoxStylesForMobileOrLower, setMainImageBoxStylesForMobileOrLower] = useState<React.CSSProperties>({});
  const [secondaryImageBoxStylesForTablet, setSecondaryImageBoxStylesForTablet] = useState<React.CSSProperties>({});
  const [secondaryImageBoxStylesForMobileOrLower, setSecondaryImageBoxStylesForMobileOrLower] = useState<React.CSSProperties>({});
  const [mainBackgroundSvgBoxStylesForSmallest, setMainBackgroundSvgBoxStylesForSmallest] = useState<React.CSSProperties>({});

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
      });
    }
  }, [isTablet, isMobile, isSmallest]);

  return (
    <Grid item xs={12} md={6} sx={{ ...styles.container }}>
      {/* Main Background SVG */}
      <Box
        sx={{
          ...styles.mainBackgroundSvgBox,
          ...mainBackgroundSvgBoxStylesForSmallest,
          paddingTop: (isSmallest) ? '0' : '58px',
        }}>
        <ForWhoMainBackgroundSvg />
      </Box>

      {/*  Main Image Box */}
      <Box
        sx={{
          ...styles.mainImageBox,
          ...mainImageBoxStylesForTablet,
          ...mainImageBoxStylesForMobileOrLower,
        }}>
        <img src={mainImageSrc} alt={mainImageTitle}
             style={{ maxWidth: '100%', objectFit: 'cover' }} />
      </Box>

      {/*  Secondary Image Box */}
      <Box
        sx={{
          ...styles.secondaryImageBox,
          ...secondaryImageBoxStylesForTablet,
          ...secondaryImageBoxStylesForMobileOrLower,
        }}>
        <img src={secondaryImageSrc} alt={secondaryImageTitle}
             style={{ maxWidth: '100%', objectFit: 'cover' }} />
      </Box>
    </Grid>
  );
}
