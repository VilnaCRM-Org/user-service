import { Stack } from '@mui/material';
import Image from 'next/image';
import React from 'react';

import UITypography from '@/components/ui/UITypography/UITypography';

interface SocialItemProps {
  // item: {
  //   id: number;
  //   icon: string;
  //   title: string;
  //   linkHref: string;
  // };
  item: any;
}

function SocialItem({ item }: SocialItemProps) {
  return (
    <Stack
      direction="row"
      gap="9px"
      alignItems="center"
      justifyContent="center"
      sx={{
        width: '188px',
        py: '18px',
        borderRadius: '12px',
        border: '1px solid  #E1E7EA',
        background: '#FFF',
      }}
    >
      <Image src={item.icon} alt={item.title} width={22} height={22} />
      <UITypography variant="demi18">{item.title}</UITypography>
    </Stack>
  );
}

export default SocialItem;
