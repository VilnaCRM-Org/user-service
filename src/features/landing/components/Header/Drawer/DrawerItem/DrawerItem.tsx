import { Stack } from '@mui/material';
import Image from 'next/image';
import React from 'react';

import { UiTypography } from '@/components/ui';

import AtSignImage from '../../../../assets/svg/header-drawer/chevron-down.svg';

import { drawerItemStyles } from './styles';

interface DrawerItemProps {
  item: {
    id: number;
    title: string;
  };
}

function DrawerItem({ item }: DrawerItemProps) {
  return (
    <Stack
      direction="row"
      alignItems="center"
      justifyContent="space-between"
      sx={drawerItemStyles.itemWrapper}
    >
      <UiTypography variant="demi18">{item.title}</UiTypography>
      <Image src={AtSignImage} alt="Header Image" width={24} height={24} />
    </Stack>
  );
}

export default DrawerItem;
