import { AppBar } from '@mui/material';
import Image from 'next/image';
import React from 'react';

import { UiToolbar } from '../../../../components/ui';
import Logo from '../../assets/svg/Logo/Logo.svg';

import { AuthenticationButtons } from './AuthenticationButtons';
import { Drawer } from './Drawer';
import { NavLink } from './NavLink';
import { headerStyles } from './styles';
import styles from './styles.module.scss';

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
  return (
    <AppBar position="static" sx={headerStyles.headerWrapper}>
      <UiToolbar>
        <Image
          src={Logo}
          alt="Header Image"
          width={131}
          height={44}
          className={styles.logo}
        />
        <NavLink links={links} />
        <AuthenticationButtons />
        <Drawer />
      </UiToolbar>
    </AppBar>
  );
}

export default Header;
