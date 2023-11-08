import { Box, Container, Typography } from '@mui/material';
import {
  ABOUT_VILNA_SECTION_MAIN_BACKGROUND_SHAPE_IN_BASE64,
} from '@/features/landing/utils/constants/constants';
import AboutVilnaSecondaryShape from '@/features/landing/assets/img/AboutVilnaSecondaryShape.png';
import DummyContainerImg from '@/features/landing/assets/img/DummyContainerImg.png';
import { useTranslation } from 'react-i18next';
import { Button } from '@/components/ui/Button/Button';
import {
  scrollToRegistrationSection,
} from '@/features/landing/utils/helpers/scrollToRegistrationSection';
import { useScreenSize } from '@/features/landing/hooks/useScreenSize/useScreenSize';
import { useEffect } from 'react';

const sectionStyle: React.CSSProperties = {
  backgroundImage: `url(${ABOUT_VILNA_SECTION_MAIN_BACKGROUND_SHAPE_IN_BASE64})`,
  backgroundRepeat: 'no-repeat',
  backgroundSize: '100% 100%',
  backgroundPosition: 'center',
  width: '100%',
  height: '1070px',
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
  height: '493px',
  display: 'flex',
  flexDirection: 'column',
  justifyContent: 'flex-end',
  alignItems: 'center',
  borderRadius: '48px',
  overflow: 'hidden',
};

const imgStyle: React.CSSProperties = {
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
  const { isTablet } = useScreenSize();

  const handleTryItOutButtonClick = () => {
    scrollToRegistrationSection();
  };

  let mainBoxStylesForLaptop: React.CSSProperties = {};

  useEffect(() => {
    console.log({ isTablet });
    if (isTablet) {
      mainBoxStylesForLaptop = {
        marginLeft: '31.7px',
        marginRight: '31.7px',
      };
    }
  }, [isTablet]);

  return (
    <Box style={{ ...sectionStyle, ...mainBoxStylesForLaptop }}>
      <Container sx={mainContentContainerStyle}>
        <Typography variant={'h1'}
                    sx={{
                      color: '#1A1C1E',
                      fontFamily: 'GolosText-Regular, sans-serif',
                      fontSize: '56px',
                      fontStyle: 'normal',
                      fontWeight: 700,
                      lineHeight: 'normal',
                      maxWidth: '680px',
                      marginTop: '80px',
                    }}>
          {t('Перша українська CRM з відкритим кодом')}
        </Typography>
        <Typography variant={'body1'} sx={{
          marginTop: '16px',
          color: '#1A1C1E',
          textAlign: 'center',
          fontFamily: 'GolosText-Regular, sans-serif',
          fontSize: '18px',
          fontStyle: 'normal',
          fontWeight: 400,
          lineHeight: '30px',
          maxWidth: '692px',
        }}>
          {t('Наша мета — підтримати українських підприємців. Саме тому ми створили Vilna, зручну та безкоштовну CRM-систему — аби ви могли займатися бізнесом, а не витрачати час на налаштування')}
        </Typography>
        <Button onClick={handleTryItOutButtonClick} customVariant={'light-blue'} buttonSize={'big'}
                style={{
                  marginTop: '16px',
                }}>
          {t('Спробувати')}
        </Button>
      </Container>
      <Container sx={backgroundSvgContainerStyle} />
      <Container sx={bottomContainerStyle}>
        <Box sx={{
          display: 'flex',
          justifyContent: 'center',
        }}>
          {/* DUMMY IMAGE, replace with real picture in production */}
          <img src={DummyContainerImg.src} alt='Dummy Img (should be replaced)' style={imgStyle} />
        </Box>
      </Container>
    </Box>
  );
}
