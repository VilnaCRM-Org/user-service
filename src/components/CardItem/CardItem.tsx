'use client';

import { Box } from '@mui/material';
import Image from 'next/image';
import React from 'react';
import { useTranslation } from 'react-i18next';

import UITypography from '../ui/UITypography/UITypography';

interface CardItemInterface {
  item: {
    id: string;
    imageSrc: string;
    title: string;
    text: string;
    width?: number;
    height?: number;
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
          <Image
            src={item.imageSrc}
            alt="Card Image"
            width={item.width}
            height={item.height}
          />
          <UITypography variant="h6" sx={{ pt: '32px' }}>
            {t(item.title)}
          </UITypography>
          <UITypography variant="bodyText16" sx={{ mt: '0.75rem' }}>
            {t(item.text)}
          </UITypography>
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
          <UITypography variant="h5" sx={{ pt: '12px' }}>
            {t(item.title)}
          </UITypography>
          <UITypography variant="bodyText18" sx={{ mt: '0.75rem' }}>
            {t(item.text)}
          </UITypography>
        </Box>
      )}
    </Box>
  );
}

export default CardItem;
