import { AppBar } from '@mui/material';
import React from 'react';
import { useTranslation } from 'react-i18next';

import { UiImage, UiToolbar } from '@/components';

import Logo from '../../assets/svg/logo/Logo.svg';

import { AuthenticationButtons } from './AuthButtons';
import { links } from './dataArray';
import { Drawer } from './Drawer';
import { NavLink } from './NavLink';
import { headerStyles } from './styles';

function Header() {
  const { t } = useTranslation();
  return (
    <AppBar position="static" sx={headerStyles.headerWrapper}>
      <UiToolbar>
        <UiImage
          src={Logo}
          alt={t('header.image_alt')}
          sx={headerStyles.logo}
        />
        <NavLink links={links} />
        <AuthenticationButtons />
        <Drawer />
      </UiToolbar>
    </AppBar>
  );
}

export default Header;
