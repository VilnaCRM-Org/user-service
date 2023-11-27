import { Box } from '@mui/material';
import React from 'react';

import AboutVilnaMainShape from '@/features/landing/assets/svg/AboutVilnaMainShape.svg';
import AboutVilnaMainContent from '@/features/landing/components/AboutVilnaSection/AboutVilnaMainContent/AboutVilnaMainContent';
import AboutVilnaSecondaryImages from '@/features/landing/components/AboutVilnaSection/AboutVilnaSecondaryImages/AboutVilnaSecondaryImages';
import { scrollToRegistrationSection } from '@/features/landing/utils/helpers/scrollToRegistrationSection';
import { useScreenSize } from '@/features/landing/hooks/useScreenSize/useScreenSize';

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
};

export default function AboutVilnaSection() {
  const { isTablet } = useScreenSize();

  const handleTryItOutButtonClick = () => {
    scrollToRegistrationSection();
  };

  return (
    <Box
      sx={{
        ...styles.allSectionStyle,
        ...(isTablet ? styles.allSectionTabletStyle : {})
      }}
    >
      {/* Main Content (like: headings, text, button etc.) */}
      <AboutVilnaMainContent onTryItOutButtonClick={handleTryItOutButtonClick} />
      {/* Images Container */}
      <AboutVilnaSecondaryImages />
    </Box>
  );
}
