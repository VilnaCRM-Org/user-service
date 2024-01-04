import { Stack } from '@mui/material';
import Image from 'next/image';
import React from 'react';

import UITypography from '@/components/ui/UITypography/UITypography';

import AtSignImage from '../../../../assets/svg/header-drawer/chevron-down.svg';

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
      sx={{
        borderRadius: '8px',
        background: '#F5F6F7',
        padding: '19px 20px',
      }}
    >
      <UITypography variant="demi18">{item.title}</UITypography>
      <Image src={AtSignImage} alt="Header Image" width={24} height={24} />
    </Stack>
  );
}

export default DrawerItem;
