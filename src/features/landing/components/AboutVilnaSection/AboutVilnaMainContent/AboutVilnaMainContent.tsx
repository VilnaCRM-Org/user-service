import { Box, Typography } from '@mui/material';
import React from 'react';
import { useTranslation } from 'react-i18next';

import { Button } from '@/components/ui/Button/Button';
import { useScreenSize } from '@/features/landing/hooks/useScreenSize/useScreenSize';
import { TRANSLATION_NAMESPACE } from '@/features/landing/utils/constants/constants';

interface IAboutVilnaMainContentProps {
  onTryItOutButtonClick: () => void;
}

const mainContentContainerStyle: React.CSSProperties = {
  display: 'flex',
  flexDirection: 'column',
  alignItems: 'center',
  textAlign: 'center',
  marginBottom: '58px',
};

export function AboutVilnaMainContent({ onTryItOutButtonClick }: IAboutVilnaMainContentProps) {
  const { t } = useTranslation(TRANSLATION_NAMESPACE);
  const { isMobile, isSmallest } = useScreenSize();

  return (
    <Box sx={mainContentContainerStyle}>
      <Typography
        variant="h1"
        style={{
          color: '#1A1C1E',
          fontFamily: 'GolosText-Regular, sans-serif',
          fontSize: isMobile || isSmallest ? '32px' : '56px',
          fontStyle: 'normal',
          fontWeight: 700,
          lineHeight: 'normal',
          maxWidth: '680px',
          marginTop: isMobile || isSmallest ? '32px' : '80px',
          textAlign: isMobile || isSmallest ? 'left' : 'inherit',
        }}
      >
        {t('about_vilna.heading_main')}
      </Typography>
      <Typography
        variant="body1"
        style={{
          marginTop: '16px',
          color: '#1A1C1E',
          fontFamily: 'GolosText-Regular, sans-serif',
          fontSize: isMobile || isSmallest ? '15px' : '18px',
          fontStyle: 'normal',
          fontWeight: 400,
          lineHeight: '30px',
          maxWidth: '692px',
          textAlign: isMobile || isSmallest ? 'left' : 'inherit',
        }}
      >
        {t('about_vilna.text_main')}
      </Typography>
      <Button
        onClick={onTryItOutButtonClick}
        customVariant="light-blue"
        buttonSize="big"
        style={{
          marginTop: '16px',
          alignSelf: isMobile || isSmallest ? 'flex-start' : 'center',
        }}
      >
        {t('about_vilna.button_main')}
      </Button>
    </Box>
  );
}
