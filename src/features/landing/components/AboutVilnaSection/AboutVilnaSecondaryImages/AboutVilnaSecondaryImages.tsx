import { Box, Container } from '@mui/material';

import { useScreenSize } from '@/features/landing/hooks/useScreenSize/useScreenSize';

import AboutVilnaSecondaryShape from '../../../../../../public/assets/img/AboutVilnaSecondaryShape.png';
import AboutVilnaBackgroundWithMainSvg from '@/features/landing/components/AboutVilnaSection/AboutVilnaBackgroundWithMainSvg/AboutVilnaBackgroundWithMainSvg';
import AboutVilnaDesktopNotchSvg from '@/features/landing/components/AboutVilnaSection/AboutVilnaDesktopNotchSvg/AboutVilnaDesktopNotchSvg';
import AboutVilnaMainCRMImage from '@/features/landing/components/AboutVilnaSection/AboutVilnaMainCRMImage/AboutVilnaMainCRMImage';
import AboutVilnaBackgroundWithSecondarySvg from '../AboutVilnaBackgroundWithSecondarySvg/AboutVilnaBackgroundWithSecondarySvg';

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
  outerContainer: {
    display: 'flex',
    justifyContent: 'center',
    margin: '0 auto',
    position: 'relative',
    height: '33.9375rem', // 543px
    width: '100%',
  },
  containerWithCRMImage: {
    width: '100%',
    height: '100%',
    maxWidth: '100%',
    display: 'flex',
    justifyContent: 'center',
    position: 'absolute',
    bottom: 0,
    zIndex: '900',
  },
  backgroundImageContainer: {
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
  backgroundImageContainerTablet: {
    maxWidth: '60rem', // 960px
  },
};

export default function AboutVilnaSecondaryImages() {
  const { isMobile, isSmallest, isDesktop, isLaptop, isTablet, isSmallTablet, isBigTablet } =
    useScreenSize();

  const contentForBigTabletOrLaptopOrDesktop = (
    <AboutVilnaBackgroundWithMainSvg>
      <AboutVilnaDesktopNotchSvg />
      <AboutVilnaMainCRMImage
        imageAltText={CRM_IMAGES.desktop.imageAltText}
        imageSrc={CRM_IMAGES.desktop.imageSrc}
      />
    </AboutVilnaBackgroundWithMainSvg>
  );

  const contentForSmallTabletOrMobileOrSmaller = (
    <AboutVilnaBackgroundWithSecondarySvg>
      <AboutVilnaMainCRMImage
        imageAltText={CRM_IMAGES.mobile.imageAltText}
        imageSrc={CRM_IMAGES.mobile.imageSrc}
      />
    </AboutVilnaBackgroundWithSecondarySvg>
  );

  return (
    <Box
      sx={{
        ...styles.outerContainer,
        justifySelf: isMobile || isSmallest ? 'start' : 'stretch',
        height: isMobile || isSmallest ? '17.75rem' : '34.4375rem', // 284px and 551px
      }}
    >
      <Container
        sx={{
          ...styles.containerWithCRMImage,
        }}
      >
        {isDesktop || isLaptop || isBigTablet ? contentForBigTabletOrLaptopOrDesktop : null}

        {isMobile || isSmallest || isSmallTablet ? contentForSmallTabletOrMobileOrSmaller : null}
      </Container>
      <Container
        sx={{
          ...styles.backgroundImageContainer,
          ...(isTablet ? styles.backgroundImageContainerTablet : {}),
        }}
      />
    </Box>
  );
}
