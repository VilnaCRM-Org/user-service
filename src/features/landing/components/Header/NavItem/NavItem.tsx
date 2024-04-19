import { Link, ListItem } from '@mui/material';
import React from 'react';
import { useTranslation } from 'react-i18next';

import { UiTypography } from '@/components/';

import styles from './styles';
import { NavProps } from './types';

function NavItem({ item, handleClick }: NavProps): React.ReactElement {
  const { t } = useTranslation();
  const isHeader: boolean = item.type === 'header';

  return (
    <ListItem sx={isHeader ? styles : styles.itemDrawerWrapper}>
      <Link
        href={item.link}
        sx={isHeader ? styles.link : styles.drawerLink}
        onClick={handleClick}
      >
        {isHeader ? (
          <UiTypography variant="medium15" sx={styles.navText}>
            {t(item.title)}
          </UiTypography>
        ) : (
          <UiTypography variant="demi18" sx={styles.navText}>
            {t(item.title)}
          </UiTypography>
        )}
      </Link>
    </ListItem>
  );
}
export default NavItem;
