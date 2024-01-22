import { Stack } from '@mui/material';
import React from 'react';

import { INavList } from '../../../../types/drawer/navigation';
import { NavItem } from '../NavItem';

import { drawerListStyles } from './styles';

function NavList({ navList }: INavList) {
  return (
    <Stack direction="column" gap="6px" sx={drawerListStyles.listWrapper}>
      {navList.map(item => (
        <NavItem item={item} key={item.id} />
      ))}
    </Stack>
  );
}

export default NavList;
