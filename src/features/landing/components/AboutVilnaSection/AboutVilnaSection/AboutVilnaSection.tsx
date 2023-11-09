import { useEffect } from 'react';
import { Box, Container, Typography } from '@mui/material';
import {
  ABOUT_VILNA_SECTION_MAIN_BACKGROUND_SHAPE_IN_BASE64,
} from '@/features/landing/utils/constants/constants';
import AboutVilnaSecondaryShape from '@/features/landing/assets/img/AboutVilnaSecondaryShape.png';
import DummyContainerImg from '@/features/landing/assets/img/DummyContainerImg.png';
import MobileViewDummyContainerImg
  from '@/features/landing/assets/img/MobileViewDummyContainerImg.png';
import { useTranslation } from 'react-i18next';
import { Button } from '@/components/ui/Button/Button';
import {
  scrollToRegistrationSection,
} from '@/features/landing/utils/helpers/scrollToRegistrationSection';
import { useScreenSize } from '@/features/landing/hooks/useScreenSize/useScreenSize';

const allSectionStyle: React.CSSProperties = {
  backgroundImage: `url(${ABOUT_VILNA_SECTION_MAIN_BACKGROUND_SHAPE_IN_BASE64})`,
  backgroundRepeat: 'no-repeat',
  backgroundSize: '100% 100%',
  backgroundPosition: 'center',
  width: '100%',
  minHeight: 'calc(1070px - 58px)', // 58px is the space between, 1070px is the default height of section
  position: 'relative',
  display: 'flex',
  flexDirection: 'column',
  alignItems: 'center',
  padding: '10px',
};

const mainContentContainerStyle: React.CSSProperties = {
  display: 'flex',
  flexDirection: 'column',
  alignItems: 'center',
  textAlign: 'center',
};

const backgroundSvgContainerStyle: React.CSSProperties = {
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

export function AboutVilnaSection() {
  const { t } = useTranslation();
  const { isTablet, isMobile, isSmallest } = useScreenSize();

  const handleTryItOutButtonClick = () => {
    scrollToRegistrationSection();
  };

  let mainBoxStylesForTablet: React.CSSProperties = {};
  let mainContentContainerStylesForMobileOrLower: React.CSSProperties = {};
  let mainBoxStylesForMobileOrLower: React.CSSProperties = {};

  useEffect(() => {
    if (isTablet) {
      mainBoxStylesForTablet = {
        marginLeft: '31.7px',
        marginRight: '31.7px',
      };
    }

    if (isMobile || isSmallest) {
      mainContentContainerStylesForMobileOrLower = {
        display: 'flex',
        flexDirection: 'column',
        alignItems: 'flex-start',
        textAlign: 'left',
      };

      mainBoxStylesForMobileOrLower = {
        minHeight: 'calc(1070px - 71px)',
      };
    }
  }, [isTablet, isMobile, isSmallest]);

  return (
    <Box
      sx={{
        ...allSectionStyle,
        ...mainBoxStylesForTablet,
        ...mainBoxStylesForMobileOrLower,
      }}>
      {/* Main Content (like: headings, text, button etc.) */}
      <Container
        sx={{
          ...mainContentContainerStyle,
          ...mainContentContainerStylesForMobileOrLower,
        }}>
        <Typography variant={'h1'}
                    sx={{
                      color: '#1A1C1E',
                      fontFamily: 'GolosText-Regular, sans-serif',
                      fontSize: (isMobile || isSmallest) ? '32px' : '56px',
                      fontStyle: 'normal',
                      fontWeight: 700,
                      lineHeight: 'normal',
                      maxWidth: '680px',
                      marginTop: (isMobile || isSmallest) ? '32px' : '80px',
                      textAlign: (isMobile || isSmallest) ? 'left' : 'inherit',
                    }}>
          {t('Перша українська CRM з відкритим кодом')}
        </Typography>
        <Typography variant={'body1'} sx={{
          marginTop: '16px',
          color: '#1A1C1E',
          fontFamily: 'GolosText-Regular, sans-serif',
          fontSize: (isMobile || isSmallest) ? '15px' : '18px',
          fontStyle: 'normal',
          fontWeight: 400,
          lineHeight: '30px',
          maxWidth: '692px',
          textAlign: (isMobile || isSmallest) ? 'left' : 'inherit',
        }}>
          {t('Наша мета — підтримати українських підприємців. Саме тому ми створили Vilna, зручну та безкоштовну CRM-систему — аби ви могли займатися бізнесом, а не витрачати час на налаштування')}
        </Typography>
        <Button onClick={handleTryItOutButtonClick} customVariant={'light-blue'} buttonSize={'big'}
                style={{
                  marginTop: '16px',
                  marginBottom: (isMobile || isSmallest) ? '30px' : '0px',
                  alignSelf: (isMobile || isSmallest) ? 'flex-start' : 'center',
                }}>
          {t('Спробувати')}
        </Button>
      </Container>

      {/* Images Container */}
      <Container sx={{
        maxWidth: '100%',
        display: 'flex',
        justifyContent: 'center',
        alignItems: (isMobile || isSmallest) ? 'flex-start' : 'stretch',
      }}>
        <Container sx={backgroundSvgContainerStyle} />
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
      </Container>
    </Box>
  );
}
