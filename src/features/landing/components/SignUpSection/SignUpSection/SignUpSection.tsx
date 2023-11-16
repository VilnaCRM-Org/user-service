import { Box, Container, Grid } from '@mui/material';
import { SignUpTextsContent } from '@/features/landing/components/SignUpSection/SignUpTextsContent/SignUpTextsContent';
import { SignUp } from '@/features/landing/components/SignUpSection/SignUp/SignUp';
import { SignUpWrapperWithBackground } from '../SignUpWrapperWithBackground/SignUpWrapperWithBackground';
import { SIGN_UP_SECTION_ID, SOCIAL_LINKS } from '@/features/landing/utils/constants/constants';

export function SignUpSection() {
  return (
    <Box id={SIGN_UP_SECTION_ID} sx={{
      padding: '65px 43px 0 124px',
      background: '#FBFBFB',
    }}>
      <Container sx={{
        width: '100%',
        maxWidth: '1192px',
        padding: '0' }}>
        <Grid container sx={{
          display: 'flex',
          justifyContent: 'space-between',
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
