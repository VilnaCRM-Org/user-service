import { Stack, List } from '@mui/material';
import React, { CSSProperties } from 'react';

import { NavItem } from '../NavItem';

import styles from './styles';
import { NavListProps } from './types';

function NavList({ navItems, handleClick }: NavListProps): React.ReactElement {
  if (navItems.length === 0) return <>Something went wrong</>;

  const wrapperStyle: CSSProperties =
    navItems[0].type === 'header' ? styles.wrapper : styles.drawerWrapper;

  const contentStyle: CSSProperties =
    navItems[0].type === 'header' ? styles.content : styles.drawerContent;

  return (
    <Stack component="nav" sx={wrapperStyle}>
      <List sx={contentStyle}>
        {navItems.map(item => (
          <NavItem item={item} key={item.id} handleClick={handleClick} />
        ))}
      </List>
    </Stack>
  );
}

export default NavList;
