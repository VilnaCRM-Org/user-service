import { Box } from '@mui/material';
import React from 'react';

import AboutVilnaMainShape from '@/features/landing/assets/svg/AboutVilnaMainShape.svg';
import AboutVilnaMainContent from '@/features/landing/components/AboutVilnaSection/AboutVilnaMainContent/AboutVilnaMainContent';
import AboutVilnaSecondaryImages from '@/features/landing/components/AboutVilnaSection/AboutVilnaSecondaryImages/AboutVilnaSecondaryImages';
import { useScreenSize } from '@/features/landing/hooks/useScreenSize/useScreenSize';
import { scrollToRegistrationSection } from '@/features/landing/utils/helpers/scrollToRegistrationSection';

const styles = {
  allSectionStyle: {
    backgroundImage: `url(${AboutVilnaMainShape.src})`,
    backgroundRepeat: 'no-repeat',
    backgroundSize: '100% 100%',
    backgroundPosition: 'center',
    width: '100%',
    padding: '10px 10px 56px 10px',
  },
  allSectionTabletStyle: {
    paddingLeft: '31.77px',
    paddingRight: '31.77px',
  },
  allSectionMobileOrSmaller: {
    padding: '0 19px 0px 15px',
  },
};

export default function AboutVilnaSection() {
  const { isTablet, isLaptop, isMobile, isSmallest } = useScreenSize();

  const handleTryItOutButtonClick = () => {
    scrollToRegistrationSection();
  };

  return (
    <Box
      sx={{
        ...styles.allSectionStyle,
        ...(isTablet || isLaptop ? styles.allSectionTabletStyle : {}),
        ...(isSmallest || isMobile ? styles.allSectionMobileOrSmaller : {}),
      }}
    >
      {/* Main Content (like: headings, text, button etc.) */}
      <AboutVilnaMainContent onTryItOutButtonClick={handleTryItOutButtonClick} />
      {/* Images Container */}
      <AboutVilnaSecondaryImages />
    </Box>
  );
}
