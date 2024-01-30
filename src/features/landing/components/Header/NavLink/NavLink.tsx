/* eslint-disable react/jsx-no-bind */
import { ListItem, Stack, List, Link } from '@mui/material';
import React from 'react';
import { useTranslation } from 'react-i18next';

import { UiTypography } from '@/components';

import { NavLinkProps } from '../../../types/header/nav-links';

import styles from './styles';

function NavLink({ links }: NavLinkProps): React.ReactElement {
  const { t } = useTranslation();

  return (
    <Stack component="nav" sx={styles.wrapper}>
      <List sx={styles.content}>
        {links.map(({ id, link, value }) => (
          <ListItem key={id}>
            <Link href={link} sx={styles.navLink}>
              <UiTypography variant="medium15">{t(value)}</UiTypography>
            </Link>
          </ListItem>
        ))}
      </List>
    </Stack>
  );
}

export default NavLink;
