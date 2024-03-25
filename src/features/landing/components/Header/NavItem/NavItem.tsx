import { Link, ListItem } from '@mui/material';
import Image from 'next/image';
import React from 'react';
import { useTranslation } from 'react-i18next';

import { UiTypography } from '@/components/';

import AtSignImage from '../../../assets/svg/header-drawer/chevron-down.svg';

import styles from './styles';
import { NavProps } from './types';

function NavItem({ item, handleClick }: NavProps): React.ReactElement {
  const { t } = useTranslation();
  const isHeader: boolean = item.type === 'header';
  const isDrawer: boolean = item.type === 'drawer';

  return (
    <ListItem sx={isHeader ? styles : styles.itemDrawerWrapper}>
      <Link
        href={item.link}
        sx={isHeader ? styles.link : styles.drawerLink}
        onClick={handleClick}
      >
        {isHeader ? (
          <UiTypography variant="medium15">{t(item.title)}</UiTypography>
        ) : (
          <UiTypography variant="demi18" sx={styles.navText}>
            {t(item.title)}
          </UiTypography>
        )}
        {isDrawer && (
          <Image src={AtSignImage} alt={t('Vector')} width={24} height={24} />
        )}
      </Link>
    </ListItem>
  );
}
export default NavItem;
