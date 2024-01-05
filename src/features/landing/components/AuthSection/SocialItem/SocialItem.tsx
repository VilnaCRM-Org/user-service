import { Stack } from '@mui/material';
import Image from 'next/image';
import React from 'react';

import { UiTypography } from '@/components/ui';

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
        width: '189px',
        py: '17px',
        borderRadius: '12px',
        border: '1px solid  #E1E7EA',
        background: '#FFF',
      }}
    >
      <Image src={item.icon} alt={item.title} width={22} height={22} />
      <UiTypography variant="demi18">{item.title}</UiTypography>
    </Stack>
  );
}

export default SocialItem;
