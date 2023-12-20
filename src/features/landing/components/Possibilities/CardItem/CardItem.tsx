'use client';

import { Box, Grid, Typography } from '@mui/material';
import Image from 'next/image';
import React from 'react';
import { useTranslation } from 'react-i18next';

interface CardItemInterface {
  item: {
    id: string;
    imageSrc: string;
    title: string;
    text: string;
  };
}

function CardItem({ item }: CardItemInterface) {
  const { t } = useTranslation();

  return (
    <Box
      maxWidth="289px"
      sx={{
        py: '40px',
        pl: '25px',
        pr: '32px',
        borderRadius: '12px',
        border: '1px solid #EAECEE',
      }}
    >
      <Image src={item.imageSrc} alt="Card Image" width={60} height={60} />
      <Typography
        variant="h5"
        sx={{
          color: '#1A1C1E',
          fontFamily: 'Golos',
          fontSize: '28px',
          fontWeight: 'bold',
          mt: '16px',
        }}
      >
        {t(item.title)}
      </Typography>
      <Typography
        variant="body1"
        sx={{
          mt: '12px',
          color: '#1A1C1E',
          fontFamily: 'Golos',
          fontSize: '18px',
          fontWeight: 'normal',
          lineHeight: '30px',
        }}
      >
        {t(item.text)}
      </Typography>
    </Box>
  );
}

export default CardItem;
