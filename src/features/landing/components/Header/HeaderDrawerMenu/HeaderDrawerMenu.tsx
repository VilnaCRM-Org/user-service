import { Grid } from '@mui/material';
import Drawer from '@mui/material/Drawer';
import * as React from 'react';
import { useTranslation } from 'react-i18next';

import HeaderDrawerEmail from '@/features/landing/components/Header/HeaderDrawerEmail/HeaderDrawerEmail';
import HeaderDrawerSocials
  from '@/features/landing/components/Header/HeaderDrawerSocials/HeaderDrawerSocials';
import { HeaderMobileLink } from '@/features/landing/components/Header/HeaderMobileLink/HeaderMobileLink';
import HeaderTopContentInMobileView from '@/features/landing/components/Header/HeaderTopContentInMobileView/HeaderTopContentInMobileView';
import { TRANSLATION_NAMESPACE } from '@/features/landing/utils/constants/constants';

interface IHeaderDrawerMenuProps {
  isDrawerOpen: boolean;
  onToggleDrawer: () => void;
  onSignInButtonClick: () => void;
  onTryItOutButtonClick: () => void;
}

export default function HeaderDrawerMenu({
  isDrawerOpen,
  onToggleDrawer,
  onSignInButtonClick,
  onTryItOutButtonClick,
}: IHeaderDrawerMenuProps) {
  const { t } = useTranslation(TRANSLATION_NAMESPACE);
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
          maxWidth: '100%',
          width: '100%',
          margin: '0 auto',
        }}
      >
        <HeaderTopContentInMobileView
          onSignInButtonClick={onSignInButtonClick}
          onTryItOutButtonClick={onTryItOutButtonClick}
          onMobileViewDrawerClose={onToggleDrawer}
          onDrawerClose={onToggleDrawer}
        />

        <Grid item sx={{ width: '100%' }}>
          <HeaderMobileLink href="/" linkNameText={t('header.advantages')} onClick={onToggleDrawer} />
        </Grid>
        <Grid item sx={{ width: '100%' }}>
          <HeaderMobileLink href="/" linkNameText={t('header.for_who')} onClick={onToggleDrawer} />
        </Grid>
        <Grid item sx={{ width: '100%' }}>
          <HeaderMobileLink href="/" linkNameText={t('header.integration')} onClick={onToggleDrawer} />
        </Grid>
        <Grid item sx={{ width: '100%' }}>
          <HeaderMobileLink href="/" linkNameText={t('header.contacts')} onClick={onToggleDrawer} />
        </Grid>

        <Grid item sx={{ width: '100%' }}>
          <HeaderDrawerEmail />
        </Grid>

        <Grid item sx={{width: '100%', marginTop: '26px'}}>
          <HeaderDrawerSocials />
        </Grid>
      </Grid>
    </Drawer>
  );
}
