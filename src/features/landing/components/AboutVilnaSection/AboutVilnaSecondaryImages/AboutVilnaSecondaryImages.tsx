import { Box, Container } from '@mui/material';

import AboutVilnaBackgroundWithMainSvg from '@/features/landing/components/AboutVilnaSection/AboutVilnaBackgroundWithMainSvg/AboutVilnaBackgroundWithMainSvg';
import AboutVilnaDesktopNotchSvg from '@/features/landing/components/AboutVilnaSection/AboutVilnaDesktopNotchSvg/AboutVilnaDesktopNotchSvg';
import AboutVilnaIphoneBackground from '@/features/landing/components/AboutVilnaSection/AboutVilnaIphoneBackground/AboutVilnaIphoneBackground';
import AboutVilnaMainCRMImage from '@/features/landing/components/AboutVilnaSection/AboutVilnaMainCRMImage/AboutVilnaMainCRMImage';
import { useScreenSize } from '@/features/landing/hooks/useScreenSize/useScreenSize';

import AboutVilnaSecondaryShape from '../../../../../../public/assets/img/AboutVilnaSecondaryShape.png';
import AboutVilnaBackgroundWithSecondaryPng from '../AboutVilnaBackgroundWithSecondarySvg/AboutVilnaBackgroundWithSecondaryPng';

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
  outerContainerForSmallTabletOrMobileOrLower: {
    height: '328px',
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
  containerWithCRMImageSmallTabletOrMobileOrLower: {
    maxHeight: '17.75rem', // 284px
  },
  backgroundSecondaryShapeImageContainer: {
    backgroundImage: `url(${AboutVilnaSecondaryShape.src})`,
    backgroundRepeat: 'no-repeat',
    backgroundSize: 'cover',
    backgroundPosition: 'bottom',
    width: '100%',
    maxWidth: '74.5rem', // 1192px
    height: '100%',
    maxHeight: '30.8125rem', // 493px
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
  backgroundSecondaryShapeImageContainerTablet: {
    maxWidth: '60rem', // 960px
  },
  backgroundSecondaryShapeImageContainerSmallTabletOrMobileOrLower: {
    maxHeight: '284px',
    height: '100%',
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
    <AboutVilnaBackgroundWithSecondaryPng>
      <AboutVilnaMainCRMImage
        imageAltText={CRM_IMAGES.mobile.imageAltText}
        imageSrc={CRM_IMAGES.mobile.imageSrc}
        style={{
          position: 'absolute',
          zIndex: '500',
          top: '-33px',
          left: '12.16px',
          height: '316.77px',
          width: '100%',
          maxWidth: '201.925px',
        }}
      />
      <AboutVilnaIphoneBackground />
    </AboutVilnaBackgroundWithSecondaryPng>
  );

  return (
    <Box
      sx={{
        ...styles.outerContainer,
        ...(isSmallTablet || isMobile || isSmallest
          ? styles.outerContainerForSmallTabletOrMobileOrLower
          : {}),

        height: isMobile || isSmallest || isSmallTablet ? '17.75rem' : '34.4375rem', // 284px and 551px
      }}
    >
      <Container
        sx={{
          ...styles.containerWithCRMImage,
          ...(isMobile || isSmallest || isSmallTablet
            ? styles.containerWithCRMImageSmallTabletOrMobileOrLower
            : {}),
        }}
      >
        {isDesktop || isLaptop || isBigTablet ? contentForBigTabletOrLaptopOrDesktop : null}

        {isMobile || isSmallest || isSmallTablet ? contentForSmallTabletOrMobileOrSmaller : null}
      </Container>
      <Container
        sx={{
          ...styles.backgroundSecondaryShapeImageContainer,
          ...(isTablet ? styles.backgroundSecondaryShapeImageContainerTablet : {}),
          ...(isSmallTablet || isMobile || isSmallest
            ? styles.backgroundSecondaryShapeImageContainerSmallTabletOrMobileOrLower
            : {}),
        }}
      />
    </Box>
  );
}
