import { Box, Typography } from '@mui/material';
import React from 'react';
import { useTranslation } from 'react-i18next';

import useScreenSize from '../../../hooks/useScreenSize/useScreenSize';
import { TRANSLATION_NAMESPACE } from '../../../utils/constants/constants';

interface IWhyWeSectionHeaderProps {
  style?: React.CSSProperties;
}

const styles = {
  mainBox: {
    padding: '0 0 0 0',
  },
  mainBoxMobileOrLower: {
    padding: '0 15px 0 15px',
  },
  mainBoxTabletOrLaptop: {
    padding: '0 32px 0 32px',
  },
  mainHeading: {
    color: '#1A1C1E',
    textAlign: 'left',
    fontFamily: 'GolosText-Bold, sans-serif',
    fontSize: '46px',
    fontStyle: 'normal',
    fontWeight: 700,
    lineHeight: 'normal',
    marginBottom: '16px',
  },
  mainHeadingMobileOrSmaller: {
    marginBottom: '8px',
    fontSize: '28px',
  },
  text: {
    color: '#1A1C1E',
    fontFamily: 'GolosText-Regular, sans-serif',
    fontSize: '18px',
    fontStyle: 'normal',
    fontWeight: 400,
    lineHeight: '30px',
    maxWidth: '39.5rem', // 632px
    marginBottom: '40px',
  },
  textLaptopOrLower: {
    marginBottom: '32px',
  },
  textMobileOrSmaller: {
    fontSize: '15px',
    marginBottom: '24px',
    lineHeight: '25px',
    paddingRight: '20px',
  },
};

export default function WhyWeSectionHeader({ style }: IWhyWeSectionHeaderProps) {
  const { t } = useTranslation(TRANSLATION_NAMESPACE);
  const { isSmallest, isTablet, isMobile, isLaptop } = useScreenSize();

  return (
    <Box
      sx={{
        ...style,
        ...styles.mainBox,
        ...(isLaptop || isTablet ? styles.mainBoxTabletOrLaptop : {}),
        ...(isSmallest || isMobile ? styles.mainBoxMobileOrLower : {}),
      }}
    >
      <Typography
        style={{
          ...styles.mainHeading,
          ...(isMobile || isSmallest ? styles.mainHeadingMobileOrSmaller : {}),
          textAlign: 'left',
        }}
      >
        {t('why_we.heading')}
      </Typography>

      <Typography
        variant="body1"
        component="p"
        style={{
          ...styles.text,
          ...(isLaptop || isTablet ? styles.textLaptopOrLower : {}),
          ...(isSmallest || isMobile ? styles.textMobileOrSmaller : {}),
        }}
      >
        {t('why_we.subtitle')}
      </Typography>
    </Box>
  );
}

WhyWeSectionHeader.defaultProps = {
  style: {},
};
