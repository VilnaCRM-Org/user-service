import { Stack } from '@mui/material';
import Image from 'next/image';
import React from 'react';

import Logo from '../../../../assets/svg/Logo/Logo.svg';
import NavList from '../../NavList/NavList';

function Navigation() {
  return (
    <Stack
      direction="row"
      justifyContent="space-between"
      alignItems="center"
      pb="15px"
      width="100%"
    >
      <Image src={Logo} alt="Logo" width={131} height={44} />
      <NavList />
    </Stack>
  );
}

export default Navigation;
