import { Stack } from '@mui/material';
import React from 'react';

import { INavItem } from '../../../../types/drawer/navigation';
import { NavItem } from '../NavItem';

import styles from './styles';

function NavList({
  navList,
  handleClick,
}: {
  navList: INavItem[];
  handleClick: () => void;
}) {
  return (
    <Stack direction="column" gap="0.375rem" sx={styles.listWrapper}>
      {navList.map(item => (
        <NavItem item={item} key={item.id} handleClick={handleClick} />
      ))}
    </Stack>
  );
}

export default NavList;
