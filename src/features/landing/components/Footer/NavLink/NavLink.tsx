import { Box } from '@mui/material';
import Image from 'next/image';
import React from 'react';

// interface INavLink {
//   id: string;
//   icon: string;
//   title: string;
//   linkHref: string;
// }

// should fix any type

function NavLink({ item }: any) {
  return (
    <Box sx={{ margin: '10px' }}>
      <Image src={item.icon} alt={item.title} width={20} height={20} />
    </Box>
  );
}

export default NavLink;
