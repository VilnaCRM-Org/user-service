import { ListItem, Stack, List, Link } from '@mui/material';
import React from 'react';
import { useTranslation } from 'react-i18next';

import { DefaultTypography } from '@/components/UiTypography';

import { NavLinkProps } from '../../../types/header/nav-links';

import styles from './styles';

function NavLink({ links }: { links: NavLinkProps[] }): React.ReactElement {
  const { t } = useTranslation();

  return (
    <Stack component="nav" sx={styles.wrapper}>
      <List sx={styles.content}>
        {links.map(({ id, link, value }) => (
          <ListItem key={id}>
            <Link href={link} sx={styles.navLink}>
              <DefaultTypography variant="medium15">
                {t(value)}
              </DefaultTypography>
            </Link>
          </ListItem>
        ))}
      </List>
    </Stack>
  );
}

export default NavLink;
