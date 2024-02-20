import { AppBar } from '@mui/material';
import React from 'react';
import { useTranslation } from 'react-i18next';

import { UiToolbar } from '@/components';
import UiImage from '@/components/UiImage';

import Logo from '../../assets/svg/logo/Logo.svg';

import { AuthButtons } from './AuthButtons';
import { headerNavList } from './constants';
import { Drawer } from './Drawer';
import NavList from './NavList/NavList';
import styles from './styles';

function Header(): React.ReactElement {
  const { t } = useTranslation();
  return (
    <AppBar position="static" sx={styles.headerWrapper}>
      <UiToolbar>
        <UiImage src={Logo} alt={t('header.logo_alt')} sx={styles.logo} />
        <NavList navItems={headerNavList} />
        <AuthButtons />
        <Drawer />
      </UiToolbar>
    </AppBar>
  );
}

export default Header;
