import { Box, Container, Grid } from '@mui/material';

import useScreenSize from '../../../hooks/useScreenSize/useScreenSize';
import { SIGN_UP_SECTION_ID, SOCIAL_LINKS } from '../../../utils/constants/constants';
import SignUp from '../SignUp/SignUp';
import SignUpTextsContent from '../SignUpTextsContent/SignUpTextsContent';
import SignUpWrapperWithBackground from '../SignUpWrapperWithBackground/SignUpWrapperWithBackground';

const styles = {
  mainBox: {
    padding: '56px 43px 0 43px',
    background: '#FBFBFB',
    minHeight: '712px',
    height: '100%',
    position: 'relative',
    overflow: 'hidden',
  },
  mainBoxTablet: {
    padding: '56px 34px 0 34px',
  },
  mainBoxSmallest: {
    padding: '32px 15px 0 15px',
  },
  mainContainer: {
    width: '100%',
    maxWidth: '1274px',
    padding: '0',
    margin: '0 auto',
    height: '100%',
  },
  mainGrid: {
    display: 'flex',
    justifyContent: 'space-between',
    alignItems: 'center',
    height: '100%',
    width: '100%',
  },
  mainGridTablet: {
    gap: '62px',
  },
  mainGridMobileOrSmaller: {
    gap: '32px',
  },
};

export default function SignUpSection() {
  const { isTablet, isMobile, isSmallest } = useScreenSize();

  return (
    <Box
      id={SIGN_UP_SECTION_ID}
      sx={{
        ...styles.mainBox,
        ...(isTablet || isMobile || isSmallest ? { ...styles.mainBoxTablet } : {}),
        ...(isSmallest ? { ...styles.mainBoxSmallest } : {}),
        padding: isSmallest || isMobile ? '32px 15px 0 15px' : styles.mainBox.padding,
      }}
    >
      <SignUpWrapperWithBackground>
        <Container
          sx={{
            ...styles.mainContainer,
          }}
        >
          <Grid
            container
            sx={{
              ...styles.mainGrid,
              ...(isTablet ? styles.mainGridTablet : {}),
              ...(isMobile || isSmallest ? styles.mainGridMobileOrSmaller : {}),
              flexDirection: isTablet || isMobile || isSmallest ? 'column' : 'row',
            }}
          >
            <SignUpTextsContent socialLinks={SOCIAL_LINKS} />
            <SignUp />
          </Grid>
        </Container>
      </SignUpWrapperWithBackground>
    </Box>
  );
}
