import { Stack, Typography } from '@mui/material';
import Image from 'next/image';
import React from 'react';

interface SocialItemProps {
  item: {
    id: number;
    icon: string;
    title: string;
    linkHref: string;
  };
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
      <Image src={item.icon} alt={item.title} width={26} height={26} />
      <Typography>{item.title}</Typography>
    </Stack>
  );
}

export default SocialItem;
