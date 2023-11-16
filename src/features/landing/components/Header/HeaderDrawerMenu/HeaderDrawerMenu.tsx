import * as React from 'react';
import Drawer from '@mui/material/Drawer';
import { Grid } from '@mui/material';
import { HeaderMobileLink } from '@/features/landing/components/Header/HeaderMobileLink/HeaderMobileLink';
import { HeaderTopContentInMobileView } from '@/features/landing/components/Header/HeaderTopContentInMobileView/HeaderTopContentInMobileView';

interface IHeaderDrawerMenuProps {
  isDrawerOpen: boolean;
  onToggleDrawer: () => void;
  onSignInButtonClick: () => void;
  onTryItOutButtonClick: () => void;
}

export function HeaderDrawerMenu({
  isDrawerOpen,
  onToggleDrawer,
  onSignInButtonClick,
  onTryItOutButtonClick,
}: IHeaderDrawerMenuProps) {
  const drawerStyles = {
    position: 'absolute',
    bottom: isDrawerOpen ? '0' : '-100%', // Slide in from the bottom or hide
    transition: 'bottom 0.3s ease-in-out',
  };

  return (
    <Drawer
      anchor="right"
      open={isDrawerOpen}
      onClose={onToggleDrawer}
      elevation={4}
      sx={{ ...drawerStyles }}
      PaperProps={{
        sx: { width: '100%' },
      }}
    >
      <Grid
        container
        sx={{
          display: 'flex',
          flexDirection: 'column',
          justifyContent: 'space-between',
          alignItems: 'center',
          padding: '6px 15px 24px 15px',
        }}
      >
        <HeaderTopContentInMobileView
          onSignInButtonClick={onSignInButtonClick}
          onTryItOutButtonClick={onTryItOutButtonClick}
          onMobileViewDrawerClose={onToggleDrawer}
          onDrawerClose={onToggleDrawer}
        />

        <Grid item sx={{ width: '100%' }}>
          <HeaderMobileLink href="/" linkNameText="Переваги" onClick={onToggleDrawer} />
        </Grid>
        <Grid item sx={{ width: '100%' }}>
          <HeaderMobileLink href="/" linkNameText="Для кого" onClick={onToggleDrawer} />
        </Grid>
        <Grid item sx={{ width: '100%' }}>
          <HeaderMobileLink href="/" linkNameText="Інтеграція" onClick={onToggleDrawer} />
        </Grid>
        <Grid item sx={{ width: '100%' }}>
          <HeaderMobileLink href="/" linkNameText="Контакти" onClick={onToggleDrawer} />
        </Grid>
      </Grid>
    </Drawer>
  );
}
