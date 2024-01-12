import { Link, ListItem, Stack, List } from '@mui/material';
import React from 'react';
import { useTranslation } from 'react-i18next';

import { navLinkStyles } from './styles';

function NavLink({ links }: { links: { id: number; value: string }[] }) {
  const { t } = useTranslation();
  return (
    <Stack component="nav" ml="106px">
      <List sx={{ display: 'flex' }}>
        {links.map(link => (
          <ListItem key={link.id}>
            <Link href="#" sx={navLinkStyles.navLink} underline="none">
              {t(link.value)}
            </Link>
          </ListItem>
        ))}
      </List>
    </Stack>
  );
}

export default NavLink;
