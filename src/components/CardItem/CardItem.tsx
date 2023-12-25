'use client';

import { Typography, Box } from '@mui/material';
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
    <Box>
      {type === 'Possibilities' && (
        <Box
          maxWidth="18.063rem"
          sx={{
            height: '100%',
            py: '2.5rem',
            pl: '1.563rem',
            pr: '2rem',
            borderRadius: '0.75rem',
            border: '1px solid #EAECEE',
          }}
        >
          <Image src={item.imageSrc} alt="Card Image" width={60} height={60} />
          <Typography
            variant="h5"
            sx={{
              color: '#1A1C1E',
              fontFamily: 'Golos',
              fontSize: '1.75rem',
              fontWeight: 'bold',
              mt: '1rem',
            }}
          >
            {t(item.title)}
          </Typography>
          <Typography
            variant="body1"
            sx={{
              mt: '0.75rem',
              color: '#1A1C1E',
              fontFamily: 'Golos',
              fontSize: '1.125rem',
              fontWeight: 'normal',
              lineHeight: '1.875rem',
            }}
          >
            {t(item.text)}
          </Typography>
        </Box>
      )}
      {type === 'WhyUs' && (
        <Box
          key={item.id}
          sx={{
            height: '100%',
            p: '1.5rem',
            borderRadius: '0.75rem',
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
              fontSize: '1.75rem',
              fontWeight: 'bold',
              mt: '1rem',
            }}
          >
            {t(item.title)}
          </Typography>
          <Typography
            variant="body1"
            sx={{
              mt: '0.75rem',
              color: '#1A1C1E',
              fontFamily: 'Golos',
              fontSize: '1.125rem',
              fontWeight: 'normal',
              lineHeight: '1.875rem',
            }}
          >
            {t(item.text)}
          </Typography>
        </Box>
      )}
    </Box>
  );
}

export default CardItem;
