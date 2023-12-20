'use client';

import { Grid, Typography } from '@mui/material';
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
  type: 'WhyUs' | 'Possibilities';
}

function CardItem({ item, type }: CardItemInterface) {
  const { t } = useTranslation();

  return (
    <div>
      {type === 'WhyUs' && (
        <Grid
          key={item.id}
          xs={12}
          sm={6}
          md={4}
          sx={{
            p: '24px',
            borderRadius: '12px',
            border: '1px solid #EAECEE',
            boxSizing: 'border-box',
          }}
        >
          <Image src={item.imageSrc} alt="Card Image" width={70} height={70} />
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
        </Grid>
      )}
    </div>
  );
}

export default CardItem;
