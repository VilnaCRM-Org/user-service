import { Stack } from '@mui/material';
import Image from 'next/image';
import React from 'react';
import { useTranslation } from 'react-i18next';

import { UiTypography } from '@/components';

import AtSignImage from '../../../../assets/svg/header-drawer/chevron-down.svg';

import { drawerItemStyles } from './styles';
import { DrawerItemProps } from './types';

function DrawerItem({ item }: DrawerItemProps) {
  const { t } = useTranslation();
  return (
    <Stack
      direction="row"
      alignItems="center"
      justifyContent="space-between"
      sx={drawerItemStyles.itemWrapper}
    >
      <UiTypography variant="demi18">{t(item.title)}</UiTypography>
      <Image src={AtSignImage} alt="Header Image" width={24} height={24} />
    </Stack>
  );
}

export default DrawerItem;
