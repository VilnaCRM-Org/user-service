import { Grid, Box } from '@mui/material';
import {
  ForWhoMainBackgroundSvg,
} from '@/features/landing/components/ForWhoSection/ForWhoMainBackgroundSvg/ForWhoMainBackgroundSvg';

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
    zIndex: 750,
    paddingTop: '58px',
  },
  mainImageBox: {
    position: 'absolute',
    zIndex: 800,
    top: '-52px',
    right: '63px',
    maxWidth: '627px',
    width: '100%',
  },
  secondaryImageBox: {
    position: 'absolute',
    zIndex: 850,
    bottom: '44px',
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
  return (
    <Grid item xs={12} md={6} sx={{ ...styles.container }}>
      {/* Main Background SVG */}
      <Box sx={{ ...styles.mainBackgroundSvgBox }}>
        <ForWhoMainBackgroundSvg />
      </Box>

      {/*  Main Image Box */}
      <Box sx={{ ...styles.mainImageBox }}>
        <img src={mainImageSrc} alt={mainImageTitle} style={{ maxWidth: '100%', objectFit: 'contain' }} />
      </Box>

      {/*  Secondary Image Box */}
      <Box sx={{ ...styles.secondaryImageBox }}>
        <img src={secondaryImageSrc} alt={secondaryImageTitle} style={{ maxWidth: '100%' }} />
      </Box>
    </Grid>
  );
}
