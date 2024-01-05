import { Stack } from '@mui/material';
import React from 'react';

import { FOOTER_SOCIAL_LINKS } from '../../../utils/constants/constants';
import { NavLink } from '../NavLink';

function NavList() {
  return (
    <Stack
      direction="row"
      alignItems="center"
      mt="6px"
      sx={{ gap: { xs: '4px', sm: '8px', xl: '8px' } }}
    >
      {FOOTER_SOCIAL_LINKS.map(item => (
        <NavLink item={item} key={item.id} />
      ))}
    </Stack>
  );
}

export default NavList;
