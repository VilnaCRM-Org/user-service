import { Box, Typography } from '@mui/material';
import React from 'react';
import { useTranslation } from 'react-i18next';

import { Button } from '@/components/ui/Button/Button';
import { useScreenSize } from '@/features/landing/hooks/useScreenSize/useScreenSize';
import { TRANSLATION_NAMESPACE } from '@/features/landing/utils/constants/constants';

interface IAboutVilnaMainContentProps {
  onTryItOutButtonClick: () => void;
}

const styles = {
  mainContainer: {
    display: 'flex',
    flexDirection: 'column',
    alignItems: 'center',
    textAlign: 'center',
    marginBottom: '46px',
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
  headingMainMobileOrSmaller: {
    fontSize: '32px',
    marginTop: '32px',
    textAlign: 'left',
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
  textMainMobileOrSmaller: {
    fontSize: '15px',
    textAlign: 'left',
  },
};

export default function AboutVilnaMainContent({
                                                onTryItOutButtonClick,
                                              }: IAboutVilnaMainContentProps) {
  const { t } = useTranslation(TRANSLATION_NAMESPACE);
  const { isMobile, isSmallest } = useScreenSize();

  return (
    <Box sx={{ ...styles.mainContainer }}>
      <Typography
        style={{
          ...styles.headingMain,
          ...(isSmallest || isMobile ? styles.headingMainMobileOrSmaller : {}),
          textAlign: isSmallest || isMobile ? 'left' : 'inherit',
        }}
      >
        {t('about_vilna.heading_main')}
      </Typography>
      <Typography
        style={{
          ...styles.textMain,
          ...(isMobile || isSmallest ? styles.textMainMobileOrSmaller : {}),
          textAlign: isMobile || isSmallest ? 'left' : 'inherit',
        }}
      >
        {t('about_vilna.text_main')}
      </Typography>
      <Button
        onClick={onTryItOutButtonClick}
        customVariant='light-blue'
        buttonSize='big'
        style={{
          marginTop: '39px',
          alignSelf: isMobile || isSmallest ? 'flex-start' : 'center',
        }}
      >
        {t('about_vilna.button_main')}
      </Button>
    </Box>
  );
}
