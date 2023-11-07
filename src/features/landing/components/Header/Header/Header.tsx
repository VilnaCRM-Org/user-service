import * as React from 'react';
import { AppBar, Container, IconButton, Toolbar } from '@mui/material';

import { VilnaMenuIcon } from '../../Icons/VilnaMenuIcon/VilnaMenuIcon';
import { useScreenSize } from '@/features/landing/hooks/useScreenSize/useScreenSize';
import { CustomLink } from '@/components/ui/CustomLink/CustomLink';
import { VilnaMainIcon } from '../../Icons/VilnaMainIcon/VilnaMainIcon';

import {
  HeaderMainLinks,
} from '@/features/landing/components/Header/HeaderMainLinks/HeaderMainLinks';
import {
  HeaderActionButtons,
} from '@/features/landing/components/Header/HeaderActionButtons/HeaderActionButtons';
import { HeaderDrawerMenu } from '../HeaderDrawerMenu/HeaderDrawerMenu';
import { useState } from 'react';

type Position =
  'sticky'
  | 'relative'
  | 'absolute'
  | 'fixed'
  | 'static'
  | 'initial'
  | 'inherit'
  | 'unset';

const appBarContainerStyle: {
  height: string;
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
} = {
  height: '64px',
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
};

const logoStyle = {
  textDecoration: 'none',
  color: 'black',
  width: '130px',
  justifySelf: 'flex-start',
};

const appBarStylesIfScreenResolutionIsLaptop = (isLaptop: boolean) => {
  if (isLaptop) {
    return {
      paddingLeft: '32px',
      paddingRight: '32px',
    };
  }
  return {
    paddingLeft: '0',
    paddingRight: '0',
  };
};

export function Header() {
  const [isDrawerOpen, setIsDrawerOpen] = useState(false);
  const {
    isSmallest,
    isMobile,
    isTablet,
    isLaptop,
    isDesktop,
  } = useScreenSize();

  const toggleDrawer = () => {
    setIsDrawerOpen(!isDrawerOpen);
  };

  const handleMenuButtonClick = () => {
    toggleDrawer();
  };

  const handleSignInButtonClick = () => {
  };

  const handleTryItOutButtonClick = () => {
  };

  return (
    <AppBar sx={{
      ...appBarContainerStyle,
      ...(appBarStylesIfScreenResolutionIsLaptop(isLaptop)),
    }} elevation={0}>
      <Container sx={{
        width: '100%',
        '& .MuiContainer-root': {
          paddingLeft: 0,
          paddingRight: 0,
        },
      }}>
        <Toolbar disableGutters
                 sx={{
                   display: 'flex',
                   justifyContent: 'space-between',
                   alignItems: 'center',
                   maxWidth: '100%',
                 }}>
          {/* Main Vilna Icon */}
          <CustomLink href={'/'}
                      style={logoStyle}>
            <VilnaMainIcon />
          </CustomLink>

          {/* Menu Icon */}
          <IconButton
            onClick={handleMenuButtonClick}
            edge='start' color='inherit' aria-label='menu'
            sx={{
              display: (isMobile || isSmallest) ? 'inline-block' : 'none',
              justifySelf: 'flex-end',
            }}>
            <VilnaMenuIcon isActive={false} />
          </IconButton>

          {/* Header Main Links */}
          <HeaderMainLinks />

          {/* Header Action Buttons */}
          <HeaderActionButtons onSignInButtonClick={handleSignInButtonClick}
                               onTryItOutButtonClick={handleTryItOutButtonClick} />
        </Toolbar>
      </Container>
      <HeaderDrawerMenu isDrawerOpen={isDrawerOpen} onToggleDrawer={toggleDrawer}
                        onSignInButtonClick={handleSignInButtonClick}
                        onTryItOutButtonClick={handleTryItOutButtonClick} />
    </AppBar>
  );
}

