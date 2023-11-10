import { useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { Container, Typography } from '@mui/material';

import { Button } from '@/components/ui/Button/Button';
import { useScreenSize } from '@/features/landing/hooks/useScreenSize/useScreenSize';

interface IAboutVilnaMainContentProps {
  onTryItOutButtonClick: () => void;
}

const mainContentContainerStyle: React.CSSProperties = {
  display: 'flex',
  flexDirection: 'column',
  alignItems: 'center',
  textAlign: 'center',
};

export function AboutVilnaMainContent({ onTryItOutButtonClick }: IAboutVilnaMainContentProps) {
  const { t } = useTranslation();
  const { isMobile, isSmallest } = useScreenSize();

  let mainContentContainerStylesForMobileOrLower: React.CSSProperties = {};

  useEffect(() => {
    if (isMobile || isSmallest) {
      mainContentContainerStylesForMobileOrLower = {
        display: 'flex',
        flexDirection: 'column',
        alignItems: 'flex-start',
        textAlign: 'left',
      };
    }
  }, [isMobile, isSmallest]);

  return (
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
      <Button onClick={onTryItOutButtonClick} customVariant={'light-blue'} buttonSize={'big'}
              style={{
                marginTop: '16px',
                marginBottom: (isMobile || isSmallest) ? '30px' : '0px',
                alignSelf: (isMobile || isSmallest) ? 'flex-start' : 'center',
              }}>
        {t('Спробувати')}
      </Button>
    </Container>
  );
}
