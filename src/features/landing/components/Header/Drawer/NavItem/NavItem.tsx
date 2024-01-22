import { Stack } from '@mui/material';
import Image from 'next/image';
import React from 'react';
import { useTranslation } from 'react-i18next';

import { UiTypography } from '@/components';

import AtSignImage from '../../../../assets/svg/header-drawer/chevron-down.svg';
import { INavItem } from '../../../../types/drawer/navigation';

import { navItemStyles } from './styles';

function NavItem({ item }: { item: INavItem }) {
  const { t } = useTranslation();
  return (
    <Stack
      direction="row"
      alignItems="center"
      justifyContent="space-between"
      sx={navItemStyles.itemWrapper}
    >
      <UiTypography variant="demi18">{t(item.title)}</UiTypography>
      <Image src={AtSignImage} alt="Header Image" width={24} height={24} />
    </Stack>
  );
}

export default NavItem;
