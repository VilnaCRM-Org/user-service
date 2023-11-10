import { useEffect } from 'react';
import { Grid } from '@mui/material';
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

const allSectionStyle: React.CSSProperties = {
  backgroundImage: `url(${AboutVilnaMainShape.src})`,
  backgroundRepeat: 'no-repeat',
  backgroundSize: '100% 100%',
  backgroundPosition: 'center',
  width: '100%',
  minHeight: 'calc(1070px - 58px)', // 58px is the space between, 1070px is the default height of section
  position: 'relative',
  display: 'flex',
  flexDirection: 'column',
  justifyContent: 'flex-start',
  gap: '58px',
  padding: '10px',
};

export function AboutVilnaSection() {
  const { isTablet, isMobile, isSmallest } = useScreenSize();

  const handleTryItOutButtonClick = () => {
    scrollToRegistrationSection();
  };

  let mainBoxStylesForTablet: React.CSSProperties = {};
  let mainBoxStylesForMobileOrLower: React.CSSProperties = {};

  useEffect(() => {
    if (isTablet) {
      mainBoxStylesForTablet = {
        marginLeft: '31.7px',
        marginRight: '31.7px',
      };
    }

    if (isMobile || isSmallest) {
      mainBoxStylesForMobileOrLower = {
        minHeight: 'calc(1070px - 71px)',
      };
    }
  }, [isTablet, isMobile, isSmallest]);

  return (
    <Grid
      container
      sx={{
        ...allSectionStyle,
        ...mainBoxStylesForTablet,
        ...mainBoxStylesForMobileOrLower,
      }}>
      {/* Main Content (like: headings, text, button etc.) */}
      <AboutVilnaMainContent onTryItOutButtonClick={handleTryItOutButtonClick} />
      {/* Images Container */}
      <AboutVilnaSecondaryImages />
    </Grid>
  );
}
