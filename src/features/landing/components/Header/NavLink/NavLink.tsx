import { Link, ListItem, Stack, List } from '@mui/material';
import React from 'react';

function NavLink({ links }: { links: { id: number; value: string }[] }) {
  return (
    <Stack component="nav" ml="106px">
      <List sx={{ display: 'flex' }}>
        {links.map(link => (
          <ListItem key={link.id}>
            <Link
              href="#"
              sx={{
                fontSize: '0.938rem',
                fontWeight: '500',
                lineHeight: '1.125rem',
                fontFamily: 'Golos',
              }}
              underline="none"
            >
              {link.value}
            </Link>
          </ListItem>
        ))}
      </List>
    </Stack>
  );
}

export default NavLink;
