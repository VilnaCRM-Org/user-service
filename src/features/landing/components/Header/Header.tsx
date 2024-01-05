import { useTheme } from '@emotion/react';
import { AppBar, useMediaQuery } from '@mui/material';
import Image from 'next/image';
import React from 'react';

import { UiToolbar } from '../../../../components/ui';
import Logo from '../../assets/svg/Logo/Logo.svg';

import { AuthenticationButtons } from './AuthenticationButtons';
import { Drawer } from './Drawer';
import { NavLink } from './NavLink';

const links = [
  { id: 1, value: 'Переваги' },
  { id: 2, value: 'Для кого' },
  { id: 3, value: 'Інтеграція' },
  { id: 4, value: 'Контакти' },
];

function Header() {
  const theme = useTheme();
  const tablet = useMediaQuery(theme.breakpoints.up('lg'));

  return (
    <AppBar
      position="static"
      sx={{
        backgroundColor: 'white',
        boxShadow: 'none',
        position: 'fixed',
        zIndex: 1000,
      }}
    >
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
