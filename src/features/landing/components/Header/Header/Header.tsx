import { AppBar, Container, IconButton, Toolbar } from '@mui/material';
import * as React from 'react';
import { useState } from 'react';

import CustomLink from '@/components/ui/CustomLink/CustomLink';
import HeaderActionButtons from '@/features/landing/components/Header/HeaderActionButtons/HeaderActionButtons';
import HeaderMainLinks from '@/features/landing/components/Header/HeaderMainLinks/HeaderMainLinks';
import VilnaMenuIcon from '@/features/landing/components/Icons/VilnaMenuIcon/VilnaMenuIcon';
import { useScreenSize } from '@/features/landing/hooks/useScreenSize/useScreenSize';
import { scrollToRegistrationSection } from '@/features/landing/utils/helpers/scrollToRegistrationSection';

import VilnaMainIcon from '../../Icons/VilnaMainIcon/VilnaMainIcon';
import HeaderDrawerMenu from '../HeaderDrawerMenu/HeaderDrawerMenu';

const styles = {
  appBar: {
    height: '100%',
    maxHeight: '64px',
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
  },
  appBarLaptopOrLower: {
    padding: '7px 32px 11px 32px',
  },
  appBarMobileOrLower: {
    padding: '0 15px 0 15px',
  },
  logo: {
    textDecoration: 'none',
    color: 'black',
    width: '130px',
    justifySelf: 'flex-start',
  },
  paddingDefault: {
    paddingLeft: '0',
    paddingRight: '0',
  },
};

export default function Header() {
  const [isDrawerOpen, setIsDrawerOpen] = useState(false);
  const { isSmallest, isMobile, isSmallTablet, isTablet, isBigTablet, isLaptop } = useScreenSize();

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
        ...styles.appBar,
        ...(isLaptop || isTablet ? styles.appBarLaptopOrLower : styles.paddingDefault),
        ...(isMobile || isSmallest ? styles.appBarMobileOrLower : {}),
        maxHeight: isTablet || isMobile || isSmallest ? '72px' : styles.appBar.maxHeight,
      }}
      elevation={0}
    >
      <Container
        sx={{
          width: '100%',
          maxWidth: '1192px',
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
          <CustomLink href="/" style={{ ...styles.logo }}>
            <VilnaMainIcon />
          </CustomLink>

          {
            /* Menu Icon */
            isMobile || isSmallest || isSmallTablet || isBigTablet ? (
              <IconButton
                onClick={handleMenuButtonClick}
                color="inherit"
                aria-label="menu"
                sx={{
                  justifySelf: 'flex-end',
                  padding: '0'
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
