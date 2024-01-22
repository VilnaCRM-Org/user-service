import { AppBar } from '@mui/material';
import React from 'react';
import { useTranslation } from 'react-i18next';

import { UiImage, UiToolbar } from '@/components';

import Logo from '../../assets/svg/logo/Logo.svg';

import { AuthenticationButtons } from './AuthButtons';
import { Drawer } from './Drawer';
import { NavLink } from './NavLink';
import { headerStyles } from './styles';

const links = [
  { id: 'Advantages', link: '#Advantages', value: 'header.advantages' },
  {
    id: 'forWhoSectionStyles',
    link: '#forWhoSectionStyles',
    value: 'header.for_who',
  },
  { id: 'Integration', link: '#Integration', value: 'header.integration' },
  { id: 'Contacts', link: '#Contacts', value: 'header.contacts' },
];

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
