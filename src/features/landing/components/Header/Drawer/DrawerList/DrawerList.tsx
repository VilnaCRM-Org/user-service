import { Stack } from '@mui/material';
import React from 'react';

import { DrawerItem } from '../DrawerItem';

import { drawerListStyles } from './styles';

function DrawerList({
  tabItems,
}: {
  tabItems: {
    id: string;
    title: string;
  }[];
}) {
  return (
    <Stack direction="column" gap="6px" sx={drawerListStyles.listWrapper}>
      {tabItems.map(item => (
        <DrawerItem item={item} key={item.id} />
      ))}
    </Stack>
  );
}

export default DrawerList;
