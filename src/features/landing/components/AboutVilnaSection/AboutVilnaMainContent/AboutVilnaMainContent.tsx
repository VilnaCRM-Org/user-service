import { Box, Typography } from '@mui/material';
import React from 'react';
import { useTranslation } from 'react-i18next';

import Button from '@/components/ui/Button/Button';

import useScreenSize from '../../../hooks/useScreenSize/useScreenSize';

interface IAboutVilnaMainContentProps {
  onTryItOutButtonClick: () => void;
}

const styles = {
  mainContainer: {
    display: 'flex',
    flexDirection: 'column',
    alignItems: 'center',
    textAlign: 'center',
    marginBottom: '48px',
  },
  mainContainerMobile: {
    marginBottom: '115px',
  },
  headingMain: {
    color: '#1A1C1E',
    fontFamily: 'GolosText-Bold, sans-serif',
    fontStyle: 'normal',
    fontWeight: 700,
    lineHeight: 'normal',
    maxWidth: '701px',
    fontSize: '56px',
    marginTop: '70px',
    textAlign: 'inherit',
  },
  headingMainLaptopOrLower: {
    marginTop: '78px',
    paddingLeft: '20px',
  },
  headingMainMobileOrSmaller: {
    fontSize: '32px',
    marginTop: '28px',
    textAlign: 'left',
    maxWidth: '341px',
  },
  textMain: {
    marginTop: '16px',
    color: '#1A1C1E',
    fontFamily: 'GolosText-Regular, sans-serif',
    fontStyle: 'normal',
    fontWeight: 400,
    lineHeight: '30px',
    maxWidth: '692px',
    fontSize: '18px',
    textAlign: 'inherit',
  },
  textMainLaptopOrLower: {
    paddingLeft: '19px',
  },
  textMainMobileOrSmaller: {
    fontSize: '15px',
    textAlign: 'left',
    lineHeight: '25px',
    marginTop: '12px',
  },
};

export default function AboutVilnaMainContent({
  onTryItOutButtonClick,
}: IAboutVilnaMainContentProps) {
  const { t } = useTranslation();
  const { isMobile, isSmallest, isLaptop, isTablet } = useScreenSize();

  return (
    <Box
      sx={{
        ...styles.mainContainer,
        ...(isMobile || isSmallest ? styles.mainContainerMobile : {}),
      }}
    >
      <Typography
        style={{
          ...styles.headingMain,
          ...(isLaptop || isTablet ? styles.headingMainLaptopOrLower : {}),
          ...(isSmallest || isMobile ? styles.headingMainMobileOrSmaller : {}),
          textAlign: isSmallest || isMobile ? 'left' : 'inherit',
        }}
      >
        {t('about_vilna.heading_main')}
      </Typography>
      <Typography
        style={{
          ...styles.textMain,
          ...(isTablet || isLaptop ? styles.textMainLaptopOrLower : {}),
          ...(isMobile || isSmallest ? styles.textMainMobileOrSmaller : {}),
          textAlign: isMobile || isSmallest ? 'left' : 'inherit',
        }}
      >
        {t('about_vilna.text_main')}
      </Typography>
      <Button
        onClick={onTryItOutButtonClick}
        customVariant="light-blue"
        buttonSize={isMobile || isSmallest ? 'medium' : 'big'}
        style={{
          marginTop: isMobile || isSmallest ? '24px' : '39px',
          alignSelf: isMobile || isSmallest ? 'flex-start' : 'center',
        }}
      >
        {t('about_vilna.button_main')}
      </Button>
    </Box>
  );
}
