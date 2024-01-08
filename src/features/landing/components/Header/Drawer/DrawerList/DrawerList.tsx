import { Stack } from '@mui/material';
import React from 'react';

import { DrawerItem } from '../DrawerItem';

import { drawerListStyles } from './styles';

const tabItems = [
  {
    id: 1,
    title: 'Переваги',
  },
  {
    id: 2,
    title: 'Для кого',
  },
  {
    id: 3,
    title: 'Інтеграція',
  },
  {
    id: 4,
    title: 'Контакти',
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
