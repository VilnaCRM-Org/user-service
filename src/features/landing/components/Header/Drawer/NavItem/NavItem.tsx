import { Link, Stack } from '@mui/material';
import Image from 'next/image';
import React from 'react';
import { useTranslation } from 'react-i18next';

import { UiTypography } from '@/components';
import { colorTheme } from '@/components/UiColorTheme';

import AtSignImage from '../../../../assets/svg/header-drawer/chevron-down.svg';
import { INavItem } from '../../../../types/drawer/navigation';

import styles from './styles';

function NavItem({
  item,
  handleClick,
}: {
  item: INavItem;
  handleClick: () => void;
}): React.ReactElement {
  const { t } = useTranslation();
  return (
    <Stack direction="row" alignItems="center" justifyContent="space-between">
      <Link onClick={handleClick} href={item.link} sx={styles.itemWrapper}>
        <UiTypography variant="demi18" color={colorTheme.palette.grey250.main}>
          {t(item.title)}
        </UiTypography>
        <Image src={AtSignImage} alt="Header Image" width={24} height={24} />
      </Link>
    </Stack>
  );
}

export default NavItem;
