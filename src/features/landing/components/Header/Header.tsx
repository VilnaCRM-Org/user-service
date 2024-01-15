import { useTheme } from '@emotion/react';
import { AppBar, useMediaQuery } from '@mui/material';
import Image from 'next/image';
import React from 'react';

import { UiToolbar } from '../../../../components/ui';
import Logo from '../../assets/svg/Logo/Logo.svg';

import { AuthenticationButtons } from './AuthenticationButtons';
import { Drawer } from './Drawer';
import { NavLink } from './NavLink';
import { headerStyles } from './styles';

const links = [
  { id: 'Advantages', value: 'header.advantages' },
  { id: 'ServiceHub', value: 'header.for_who' },
  { id: 'Integration', value: 'header.integration' },
  { id: 'Contacts', value: 'header.contacts' },
];

function Header() {
  const theme = useTheme();
  const tablet = useMediaQuery(theme.breakpoints.up('lg'));

  return (
    <AppBar position="static" sx={headerStyles.headerWrapper}>
      <UiToolbar>
        <Image src={Logo} alt="Header Image" width={131} height={44} />
        {tablet && (
          <>
            <NavLink links={links} />
            <AuthenticationButtons />
          </>
        )}
        {!tablet && <Drawer />}
      </UiToolbar>
    </AppBar>
  );
}

export default Header;
