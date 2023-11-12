import { Box, Container } from '@mui/material';
import MobileViewDummyContainerImg
  from '@/features/landing/assets/img/MobileViewDummyContainerImg.png';
import DummyContainerImg from '@/features/landing/assets/img/DummyContainerImg.png';
import { useScreenSize } from '@/features/landing/hooks/useScreenSize/useScreenSize';
import AboutVilnaSecondaryShape from '@/features/landing/assets/img/AboutVilnaSecondaryShape.png';

const outerContainerStyles: React.CSSProperties = {
  display: 'flex',
  justifyContent: 'center',
  margin: '0 auto',
  position: 'relative',
  height: '551px',
  maxWidth: '100%',
};

const crmPlaceholderImageStyle: React.CSSProperties = {
  width: '83%',
  maxWidth: '766px',
  height: '100%',
  display: 'inline-block',
  objectFit: 'cover',
};

const containerWithCRMImageStyle: React.CSSProperties = {
  width: '100%',
  height: '100%',
  display: 'flex',
  justifyContent: 'center',
  position: 'absolute',
  bottom: 0,
  zIndex: '900',
};

const backgroundImageContainerStyle: React.CSSProperties = {
  backgroundImage: `url(${AboutVilnaSecondaryShape.src})`,
  backgroundRepeat: 'no-repeat',
  backgroundSize: 'cover',
  backgroundPosition: 'bottom',
  width: '100%',
  maxWidth: '93.75%',
  height: '89%',
  display: 'flex',
  flexDirection: 'column',
  justifyContent: 'flex-end',
  alignItems: 'center',
  borderRadius: '48px',
  overflow: 'hidden',
  position: 'absolute',
  bottom: 0,
  zIndex: '800',
};

export function AboutVilnaSecondaryImages() {
  const { isMobile, isSmallest } = useScreenSize();

  return (
    <Box sx={{
      ...outerContainerStyles,
      justifySelf: (isMobile || isSmallest) ? 'start' : 'stretch',
    }}>
      <Container sx={containerWithCRMImageStyle}>
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
      <Container sx={backgroundImageContainerStyle} />
    </Box>
  );
}
