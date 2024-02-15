import { Link, Stack } from '@mui/material';
import Image from 'next/image';
import React from 'react';
import { useTranslation } from 'react-i18next';

import { DefaultTypography } from '@/components/UiTypography';

import AtSignImage from '../../../../assets/svg/header-drawer/chevron-down.svg';
import { NavItemProps } from '../../../../types/drawer/navigation';

import styles from './styles';

function NavItem({
  item,
  handleClick,
}: {
  item: NavItemProps;
  handleClick: () => void;
}): React.ReactElement {
  const { t } = useTranslation();
  return (
    <Stack direction="row" alignItems="center" justifyContent="space-between">
      <Link onClick={handleClick} href={item.link} sx={styles.itemWrapper}>
        <DefaultTypography variant="demi18" sx={styles.navText}>
          {t(item.title)}
        </DefaultTypography>
        <Image src={AtSignImage} alt={t('Vector')} width={24} height={24} />
      </Link>
    </Stack>
  );
}

export default NavItem;
