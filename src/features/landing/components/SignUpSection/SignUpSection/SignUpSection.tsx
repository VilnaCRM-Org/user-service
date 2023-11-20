import { Box, Container, Grid } from '@mui/material';

import SignUp from '@/features/landing/components/SignUpSection/SignUp/SignUp';
import SignUpTextsContent
  from '@/features/landing/components/SignUpSection/SignUpTextsContent/SignUpTextsContent';
import { useScreenSize } from '@/features/landing/hooks/useScreenSize/useScreenSize';
import { SIGN_UP_SECTION_ID, SOCIAL_LINKS } from '@/features/landing/utils/constants/constants';

import SignUpWrapperWithBackground
  from '../SignUpWrapperWithBackground/SignUpWrapperWithBackground';


export default function SignUpSection() {
  const { isTablet, isMobile, isSmallest } = useScreenSize();

  return (
    <Box id={SIGN_UP_SECTION_ID} sx={{
      padding: '65px 43px 0 43px',
      background: '#FBFBFB',
    }}>
      <Container sx={{
        width: '100%',
        maxWidth: '1274px',
        padding: '0',
        margin: '0 auto',
      }}>
        <Grid container sx={{
          display: 'flex',
          flexDirection: (isTablet || isMobile || isSmallest) ? 'column' : 'row',
          justifyContent: 'space-between',
          height: '100%',
          width: '100%',
          gap: (isTablet || isMobile || isSmallest) ? '62px' : '0',
        }}>
          <SignUpTextsContent socialLinks={SOCIAL_LINKS} />
          <SignUpWrapperWithBackground>
            <SignUp />
          </SignUpWrapperWithBackground>
        </Grid>
      </Container>
    </Box>
  );
}
