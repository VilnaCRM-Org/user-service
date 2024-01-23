/* eslint-disable react/jsx-no-bind */
import { ListItem, Stack, List, Link } from '@mui/material';
import React from 'react';
import { useTranslation } from 'react-i18next';

import { UiTypography } from '@/components';

import { NavLinkProps } from '../../../types/header/nav-links';

import { navLinkStyles } from './styles';

function NavLink({ links }: NavLinkProps) {
  const { t } = useTranslation();

  return (
    <Stack component="nav" sx={navLinkStyles.wrapper}>
      <List sx={navLinkStyles.content}>
        {links.map(({ id, link, value }) => (
          <ListItem key={id}>
            <Link href={link} sx={navLinkStyles.navLink}>
              <UiTypography variant="medium15">{t(value)}</UiTypography>
            </Link>
          </ListItem>
        ))}
      </List>
    </Stack>
  );
}

export default NavLink;
