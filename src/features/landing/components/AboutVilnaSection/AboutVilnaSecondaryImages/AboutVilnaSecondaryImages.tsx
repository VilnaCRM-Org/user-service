import { Box, Container, Grid } from '@mui/material';
import MobileViewDummyContainerImg
  from '@/features/landing/assets/img/MobileViewDummyContainerImg.png';
import DummyContainerImg from '@/features/landing/assets/img/DummyContainerImg.png';
import { useScreenSize } from '@/features/landing/hooks/useScreenSize/useScreenSize';
import AboutVilnaSecondaryShape from '@/features/landing/assets/img/AboutVilnaSecondaryShape.png';

const outerContainerStyles: React.CSSProperties = {
  maxWidth: '100%',
  display: 'flex',
  flexDirection: 'column',
  justifyContent: 'flex-start',
  alignItems: 'center',
  height: '100%',
  margin: '0 auto',
};

const backgroundImageContainerStyle: React.CSSProperties = {
  backgroundImage: `url(${AboutVilnaSecondaryShape.src})`,
  backgroundRepeat: 'no-repeat',
  backgroundSize: 'cover',
  backgroundPosition: 'bottom',
  position: 'absolute',
  bottom: 0,
  width: '100%',
  maxWidth: '93.75%',
  height: '493px',
  display: 'flex',
  flexDirection: 'column',
  justifyContent: 'flex-end',
  alignItems: 'center',
  borderRadius: '48px',
  overflow: 'hidden',
};

const crmPlaceholderImageStyle: React.CSSProperties = {
  width: '83%',
  maxWidth: '766px',
  height: '100%',
  display: 'inline-block',
  objectFit: 'cover',
};

const bottomContainerStyle: React.CSSProperties = {
  position: 'absolute',
  bottom: 0,
  width: '100%',
  height: '100%',
  maxHeight: '527px',
  display: 'flex',
  justifyContent: 'center',
};

export function AboutVilnaSecondaryImages() {
  const { isMobile, isSmallest } = useScreenSize();

  return (
    <Grid item
          sx={{
            ...outerContainerStyles,
            justifySelf: (isMobile || isSmallest) ? 'start' : 'stretch',
          }}>
      <Container sx={backgroundImageContainerStyle} />
      <Container sx={bottomContainerStyle}>
        <Box sx={{
          display: 'flex',
          justifyContent: 'center',
          maxWidth: (isMobile || isSmallest) ? '57%' : '100%',
        }}>
          {/* DUMMY IMAGE, replace with real picture in production */}
          {(isMobile || isSmallest) ? (
              <img src={MobileViewDummyContainerImg.src} alt='Dummy Img (should be replaced)'
                   style={{ ...crmPlaceholderImageStyle, objectFit: 'cover', width: '100%' }} />
            ) :
            (
              <img src={DummyContainerImg.src} alt='Dummy Img (should be replaced)'
                   style={crmPlaceholderImageStyle} />
            )
          }
        </Box>
      </Container>
    </Grid>
  );
}
