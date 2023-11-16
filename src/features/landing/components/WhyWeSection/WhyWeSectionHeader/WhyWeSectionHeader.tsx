import React from 'react';
import { Box, Typography } from '@mui/material';
import { useTranslation } from 'react-i18next';
import { useScreenSize } from '@/features/landing/hooks/useScreenSize/useScreenSize';

interface IWhyWeSectionHeaderProps {
  style?: React.CSSProperties;
}

const styles = {
  mainHeading: {
    color: '#1A1C1E',
    textAlign: 'left',
    fontFamily: 'GolosText-Regular, sans-serif',
    fontSize: '46px',
    fontStyle: 'normal',
    fontWeight: 700,
    lineHeight: 'normal',
    marginBottom: '16px',
  },
  text: {
    color: '#1A1C1E',
    fontFamily: 'GolosText-Regular, sans-serif',
    fontSize: '18px',
    fontStyle: 'normal',
    fontWeight: 400,
    lineHeight: '30px',
    maxWidth: '632px',
    marginBottom: '40px',
  },
};

export function WhyWeSectionHeader({ style }: IWhyWeSectionHeaderProps) {
  const { t } = useTranslation();
  const { isSmallest } = useScreenSize();

  return <Box sx={{ ...style }}>
    <Typography variant='h1' component='h2' sx={{
      ...styles.mainHeading,
      fontSize: (isSmallest) ? '28px' : styles.mainHeading.fontSize,
    }}>
      {t('Why we')}
    </Typography>

    <Typography variant='body1' component={'p'} sx={{
      ...styles.text,
      fontSize: (isSmallest) ? '15px' : styles.text.fontSize,
    }}>
      {t('Unlimited customization options or ease of use - we\'ve made it easy for any business to manage sales')}
    </Typography>
  </Box>;
}
