/* eslint-disable react/jsx-no-bind */
import { ListItem, Stack, List, Link } from '@mui/material';
import React from 'react';
import { useTranslation } from 'react-i18next';

import { NavLinkProps } from '../../../types/header/nav-links';

import { navLinkStyles } from './styles';

function NavLink({ links }: NavLinkProps) {
  const { t } = useTranslation();

  return (
    <Stack component="nav" sx={navLinkStyles.wrapper}>
      <List sx={{ display: 'flex' }}>
        {links.map(({ id, link, value }) => (
          <ListItem key={id}>
            <Link href={link} sx={navLinkStyles.navLink}>
              {t(value)}
            </Link>
          </ListItem>
        ))}
      </List>
    </Stack>
  );
}

export default NavLink;
