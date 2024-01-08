import { Link, ListItem, Stack, List } from '@mui/material';
import React from 'react';

import { navLinkStyles } from './styles';

function NavLink({ links }: { links: { id: number; value: string }[] }) {
  return (
    <Stack component="nav" ml="106px">
      <List sx={{ display: 'flex' }}>
        {links.map(link => (
          <ListItem key={link.id}>
            <Link href="#" sx={navLinkStyles.navLink} underline="none">
              {link.value}
            </Link>
          </ListItem>
        ))}
      </List>
    </Stack>
  );
}

export default NavLink;
