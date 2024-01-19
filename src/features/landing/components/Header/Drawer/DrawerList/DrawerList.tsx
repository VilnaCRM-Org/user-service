import { Stack } from '@mui/material';
import React from 'react';

import { DrawerItem } from '../DrawerItem';

import { drawerListStyles } from './styles';

const tabItems = [
  {
    id: 'advantages',
    title: 'header.advantages',
  },
  {
    id: 'for-who',
    title: 'header.for_who',
  },
  {
    id: 'integration',
    title: 'header.integration',
  },
  {
    id: 'contacts',
    title: 'header.contacts',
  },
];

function DrawerList() {
  return (
    <Stack direction="column" gap="6px" sx={drawerListStyles.listWrapper}>
      {tabItems.map(item => (
        <DrawerItem item={item} key={item.id} />
      ))}
    </Stack>
  );
}

export default DrawerList;
