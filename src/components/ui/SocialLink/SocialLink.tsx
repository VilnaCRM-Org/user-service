import { Box, Typography } from '@mui/material';
import { useEffect, useState } from 'react';

import { useScreenSize } from '@/features/landing/hooks/useScreenSize/useScreenSize';

import CustomLink from '../CustomLink/CustomLink';

interface ISocialLinkProps {
  icon: string;
  title: string;
  linkHref: string;
  isDisabled?: boolean;
  style?: React.CSSProperties;
}

const styles = {
  main: {
    display: 'flex',
    padding: '18px 47.5px 18px 48.5px',
    justifyContent: 'center',
    alignItems: 'center',
    gap: '9px',
    borderRadius: '12px',
    border: '1px solid #E1E7EA',
    background: '#FFF',
    width: '100%',
    maxWidth: '188px',
  },
  mainTablet: {
    padding: '18px 36px 18px 36px',
  },
  mainMobile: {
    padding: '18px 37.5px 18px 38.5px',
    maxWidth: '169px',
  },
  imageBox: {
    width: '100%',
    maxWidth: '22px',
  },
  image: {
    width: '22px',
    height: 'auto',
    objectFit: 'cover',
  },
  text: {
    color: '#1A1C1E',
    fontFamily: 'GolosText-Regular, sans-serif',
    fontSize: '18px',
    fontStyle: 'normal',
    fontWeight: '600',
    lineHeight: 'normal',
    textAlign: 'center',
    width: '100%',
  },
  mainHoverState: {
    boxShadow: '0px 4px 7px 0px rgba(116, 134, 151, 0.17)',
  },
  mainActiveState: {
    boxShadow: '0px 4px 7px 0px rgba(71, 85, 99, 0.21)',
  },
  mainDisabledState: {
    background: '#E1E7EA',
    cursor: 'not-allowed',
    opacity: '0.2',
    color: '#FFF',
  },
};

export default function SocialLink({ icon, title, linkHref, isDisabled, style }: ISocialLinkProps) {
  const [isHovered, setIsHovered] = useState(false);
  const [isActive, setIsActive] = useState(false);
  const { isTablet, isSmallest, isMobile } = useScreenSize();

  const handleMouseEnter = () => {
    if (!isDisabled) {
      setIsHovered(true);
    }
  };

  const handleMouseLeave = () => {
    setIsHovered(false);
  };

  const handleMouseDown = () => {
    if (!isDisabled) {
      setIsActive(true);
    }
  };

  const handleMouseUp = () => {
    setIsActive(false);
  };

  useEffect(() => {
    const handleGlobalMouseUp = () => {
      setIsActive(false);
    };

    document.addEventListener('mouseup', handleGlobalMouseUp);

    return () => {
      document.removeEventListener('mouseup', handleGlobalMouseUp);
    };
  }, []);

  return (
    <CustomLink
      href={linkHref}
      onMouseEnter={handleMouseEnter}
      onMouseLeave={handleMouseLeave}
      onMouseDown={handleMouseDown}
      onMouseUp={handleMouseUp}
      style={{
        ...styles.main,
        ...(isTablet ? styles.mainTablet : {}),
        ...((isMobile || isSmallest) ? styles.mainMobile : {}),
        ...(isHovered && styles.mainHoverState),
        ...(isActive && styles.mainActiveState),
        ...(isDisabled && styles.mainDisabledState),
        ...style,
      }}
    >
      <Box
        sx={{
          ...styles.imageBox,
        }}
      >
        <img
          src={icon}
          alt={title}
          style={{
            ...styles.image,
            objectFit: 'cover',
          }}
        />
      </Box>
      <Typography
        variant="body1"
        component="p"
        style={{
          ...styles.text,
          textAlign: 'center',
        }}
      >
        {title}
      </Typography>
    </CustomLink>
  );
}

SocialLink.defaultProps = {
  isDisabled: false,
  style: {},
};
