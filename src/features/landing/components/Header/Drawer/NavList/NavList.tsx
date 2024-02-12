import { Stack } from '@mui/material';
import React from 'react';

import { NavItemProps } from '../../../../types/drawer/navigation';
import NavItem from '../NavItem/NavItem';

import styles from './styles';

function NavList({
  navList,
  handleClick,
}: {
  navList: NavItemProps[];
  handleClick: () => void;
}): React.ReactElement {
  return (
    <Stack direction="column" gap="0.375rem" sx={styles.listWrapper}>
      {navList.map(item => (
        <NavItem item={item} key={item.id} handleClick={handleClick} />
      ))}
    </Stack>
  );
}

export default NavList;
