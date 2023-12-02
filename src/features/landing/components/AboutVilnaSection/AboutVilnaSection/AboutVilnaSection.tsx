import { Box } from '@mui/material';
import React from 'react';

import AboutVilnaMainShape from '../../../assets/svg/AboutVilnaMainShape.svg';
import useScreenSize from '../../../hooks/useScreenSize/useScreenSize';
import scrollToRegistrationSection from '../../../utils/helpers/scrollToRegistrationSection';
import AboutVilnaMainContent from '../AboutVilnaMainContent/AboutVilnaMainContent';
import AboutVilnaSecondaryImages from '../AboutVilnaSecondaryImages/AboutVilnaSecondaryImages';

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
