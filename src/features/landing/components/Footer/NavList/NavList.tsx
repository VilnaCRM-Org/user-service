import { Stack } from '@mui/material';
import React from 'react';

import { FOOTER_SOCIAL_LINKS } from '../../../utils/constants/constants';
import { NavLink } from '../NavLink';

import { navListStyles } from './styles';

function NavList() {
  return (
    <Stack direction="row" alignItems="center" sx={navListStyles.listWrapper}>
      {FOOTER_SOCIAL_LINKS.map(item => (
        <NavLink item={item} key={item.id} />
      ))}
    </Stack>
  );
}

export default NavList;
