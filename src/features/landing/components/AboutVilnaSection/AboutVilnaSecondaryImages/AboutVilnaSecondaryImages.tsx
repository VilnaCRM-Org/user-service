import { Box, Container } from '@mui/material';

import { useScreenSize } from '@/features/landing/hooks/useScreenSize/useScreenSize';

import AboutVilnaSecondaryShape from '../../../../../../public/assets/img/AboutVilnaSecondaryShape.png';
import AboutVilnaBackgroundWithMainSvg from '@/features/landing/components/AboutVilnaSection/AboutVilnaBackgroundWithMainSvg/AboutVilnaBackgroundWithMainSvg';
import AboutVilnaDesktopNotchSvg from '@/features/landing/components/AboutVilnaSection/AboutVilnaDesktopNotchSvg/AboutVilnaDesktopNotchSvg';
import AboutVilnaMainCRMImage from '@/features/landing/components/AboutVilnaSection/AboutVilnaMainCRMImage/AboutVilnaMainCRMImage';

const CRM_IMAGES = {
  desktop: {
    imageSrc: '/assets/img/DummyContainerImg.png',
    imageAltText: 'Vilna CRM Desktop',
  },
  mobile: {
    imageSrc: '/assets/img/MobileViewDummyContainerImg.png',
    imageAltText: 'Vilna CRM Mobile',
  },
};

const styles = {
  outerContainerStyles: {
    display: 'flex',
    justifyContent: 'center',
    margin: '0 auto',
    position: 'relative',
    height: '543px',
    maxWidth: '100%',
  },
  containerWithCRMImageStyle: {
    width: '100%',
    height: '100%',
    display: 'flex',
    justifyContent: 'center',
    position: 'absolute',
    bottom: 0,
    zIndex: '900',
  },
  backgroundImageContainerStyle: {
    backgroundImage: `url(${AboutVilnaSecondaryShape.src})`,
    backgroundRepeat: 'no-repeat',
    backgroundSize: 'cover',
    backgroundPosition: 'bottom',
    width: '100%',
    maxWidth: '1192px',
    height: '493px',
    display: 'flex',
    flexDirection: 'column',
    justifyContent: 'flex-end',
    alignItems: 'center',
    borderRadius: '48px',
    overflow: 'hidden',
    position: 'absolute',
    bottom: 0,
    zIndex: '800',
    pointerEvents: 'none',
    userSelect: 'none',
  },
};

export default function AboutVilnaSecondaryImages() {
  const { isMobile, isSmallest, isDesktop, isLaptop } = useScreenSize();

  return (
    <Box
      sx={{
        ...styles.outerContainerStyles,
        justifySelf: isMobile || isSmallest ? 'start' : 'stretch',
        height: isMobile || isSmallest ? '284px' : '551px',
      }}
    >
      <Container sx={{ ...styles.containerWithCRMImageStyle }}>
        {isDesktop || isLaptop ? (
          <AboutVilnaBackgroundWithMainSvg>
            <AboutVilnaDesktopNotchSvg />
            <AboutVilnaMainCRMImage
              imageAltText={CRM_IMAGES.desktop.imageAltText}
              imageSrc={CRM_IMAGES.desktop.imageSrc}
            />
          </AboutVilnaBackgroundWithMainSvg>
        ) : null}
      </Container>
      <Container sx={{ ...styles.backgroundImageContainerStyle }} />
    </Box>
  );
}
