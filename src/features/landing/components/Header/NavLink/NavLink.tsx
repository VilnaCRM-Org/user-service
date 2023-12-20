import { Link, ListItem, Stack, List } from '@mui/material';
import React from 'react';

function NavLink({ links }: { links: { id: number; value: string }[] }) {
  return (
    <Stack component="nav">
      <List sx={{ display: 'flex' }}>
        {links.map(link => (
          <ListItem key={link.id}>
            <Link href="#" sx={{ color: '#57595B' }} underline="none">
              {link.value}
            </Link>
          </ListItem>
        ))}
      </List>
    </Stack>
  );
}

export default NavLink;
