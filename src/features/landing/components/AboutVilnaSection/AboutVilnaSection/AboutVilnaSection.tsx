import React, { useEffect } from 'react';
import AboutVilnaMainShape from '@/features/landing/assets/svg/AboutVilnaMainShape.svg';
import {
  scrollToRegistrationSection,
} from '@/features/landing/utils/helpers/scrollToRegistrationSection';
import { useScreenSize } from '@/features/landing/hooks/useScreenSize/useScreenSize';
import {
  AboutVilnaMainContent,
} from '@/features/landing/components/AboutVilnaSection/AboutVilnaMainContent/AboutVilnaMainContent';
import {
  AboutVilnaSecondaryImages,
} from '@/features/landing/components/AboutVilnaSection/AboutVilnaSecondaryImages/AboutVilnaSecondaryImages';
import { Box } from '@mui/material';

const allSectionStyle: React.CSSProperties = {
  backgroundImage: `url(${AboutVilnaMainShape.src})`,
  backgroundRepeat: 'no-repeat',
  backgroundSize: '100% 100%',
  backgroundPosition: 'center',
  width: '100%',
  padding: '10px 10px 56px 10px',
};

export function AboutVilnaSection() {
  const { isTablet } = useScreenSize();

  const handleTryItOutButtonClick = () => {
    scrollToRegistrationSection();
  };

  let mainBoxStylesForTablet: React.CSSProperties = {};

  useEffect(() => {
    if (isTablet) {
      mainBoxStylesForTablet = {
        marginLeft: '31.7px',
        marginRight: '31.7px',
      };
    }
  }, [isTablet]);

  return (
    <Box
      sx={{
        ...allSectionStyle,
        ...mainBoxStylesForTablet,
      }}>
      {/* Main Content (like: headings, text, button etc.) */}
      <AboutVilnaMainContent onTryItOutButtonClick={handleTryItOutButtonClick} />
      {/* Images Container */}
      <AboutVilnaSecondaryImages />
    </Box>
  );
}
