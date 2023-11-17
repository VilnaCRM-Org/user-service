import { AppBar, Container, IconButton, Toolbar } from '@mui/material';
import * as React from 'react';
import { useState } from 'react';

import CustomLink from '@/components/ui/CustomLink/CustomLink';
import { HeaderActionButtons } from '@/features/landing/components/Header/HeaderActionButtons/HeaderActionButtons';
import HeaderMainLinks from '@/features/landing/components/Header/HeaderMainLinks/HeaderMainLinks';
import { useScreenSize } from '@/features/landing/hooks/useScreenSize/useScreenSize';
import { scrollToRegistrationSection } from '@/features/landing/utils/helpers/scrollToRegistrationSection';

import VilnaMainIcon from '../../Icons/VilnaMainIcon/VilnaMainIcon';
import { VilnaMenuIcon } from '../../Icons/VilnaMenuIcon/VilnaMenuIcon';
import HeaderDrawerMenu from '../HeaderDrawerMenu/HeaderDrawerMenu';

type Position =
  | 'sticky'
  | 'relative'
  | 'absolute'
  | 'fixed'
  | 'static'
  | 'initial'
  | 'inherit'
  | 'unset';

const appBarContainerStyle: {
  minHeight: string;
  position: Position | undefined; // Use the defined Position type here
  top: number;
  backgroundColor: string;
  zIndex: number;
  maxWidth: string;
  margin: string;
  display: string;
  alignItems: string;
  width: string;
  justifyContent: string;
  flexGrow: number;
} = {
  minHeight: '64px',
  position: 'sticky',
  top: 0,
  backgroundColor: 'white',
  zIndex: 1000,
  maxWidth: '100%',
  width: '100%',
  margin: '0 auto',
  display: 'flex',
  alignItems: 'center',
  justifyContent: 'space-between',
  flexGrow: 1,
};

const logoStyle = {
  textDecoration: 'none',
  color: 'black',
  width: '130px',
  justifySelf: 'flex-start',
};

const appBarStylesIfScreenResolution = (isLaptop: boolean, isSmallest: boolean) => {
  if (isLaptop) {
    return {
      paddingLeft: '32px',
      paddingRight: '32px',
    };
  }

  if (isSmallest) {
    return {
      paddingLeft: '0',
      paddingRight: '0',
    }
  }

  return {
    paddingLeft: '10px',
    paddingRight: '10px',
  };
};

export default function Header() {
  const [isDrawerOpen, setIsDrawerOpen] = useState(false);
  const { isSmallest, isMobile, isLaptop, isSmallTablet } = useScreenSize();

  const toggleDrawer = () => {
    setIsDrawerOpen(!isDrawerOpen);
  };

  const handleMenuButtonClick = () => {
    toggleDrawer();
  };

  const handleSignInButtonClick = () => {};

  const handleTryItOutButtonClick = () => {
    scrollToRegistrationSection();
  };

  return (
    <AppBar
      sx={{
        ...appBarContainerStyle,
        ...appBarStylesIfScreenResolution(isLaptop, isSmallest)
      }}
      elevation={0}
    >
      <Container
        sx={{
          width: '100%',
          '& .MuiContainer-root': {
            paddingLeft: 0,
            paddingRight: 0,
          },
        }}
      >
        <Toolbar
          disableGutters
          sx={{
            display: 'flex',
            justifyContent: 'space-between',
            alignItems: 'center',
            maxWidth: '100%',
          }}
        >
          {/* Main Vilna Icon */}
          <CustomLink href="/" style={logoStyle}>
            <VilnaMainIcon />
          </CustomLink>

          {
            /* Menu Icon */
            (isMobile || isSmallest || isSmallTablet) ? (
              <IconButton
                onClick={handleMenuButtonClick}
                edge="start"
                color="inherit"
                aria-label="menu"
                sx={{
                  justifySelf: 'flex-end',
                }}
              >
                <VilnaMenuIcon isActive={false} />
              </IconButton>
            ) : null
          }

          {/* Header Main Links */}
          <HeaderMainLinks />

          {/* Header Action Buttons */}
          <HeaderActionButtons
            onSignInButtonClick={handleSignInButtonClick}
            onTryItOutButtonClick={handleTryItOutButtonClick}
          />
        </Toolbar>
      </Container>
      <HeaderDrawerMenu
        isDrawerOpen={isDrawerOpen}
        onToggleDrawer={toggleDrawer}
        onSignInButtonClick={handleSignInButtonClick}
        onTryItOutButtonClick={handleTryItOutButtonClick}
      />
    </AppBar>
  );
}
