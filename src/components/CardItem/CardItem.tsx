import { Box, Stack } from '@mui/material';
import Image from 'next/image';
import React from 'react';
import { useTranslation } from 'react-i18next';

import UiTypography from '../ui/UiTypography/UiTypography';

import { cardItemWhyUsStyles, cardItemPossibilitiesStyles } from './styles';

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
    <>
      {type === 'Possibilities' && (
        <Stack sx={cardItemPossibilitiesStyles.wrapper}>
          <Image src={item.imageSrc} alt="Card Image" width={80} height={80} />
          <Stack flexDirection="column">
            <UiTypography variant="h6" sx={cardItemPossibilitiesStyles.title}>
              {t(item.title)}
            </UiTypography>
            <UiTypography
              variant="bodyText16"
              sx={cardItemPossibilitiesStyles.text}
              dangerouslySetInnerHTML={{ __html: t(item.text) || '' }}
            />
          </Stack>
        </Stack>
      )}

      {type === 'WhyUs' && (
        <Box sx={cardItemWhyUsStyles.wrapper}>
          <Box key={item.id} sx={cardItemWhyUsStyles.content}>
            <Box
              style={{ backgroundImage: `url(${item.imageSrc.src})` }}
              sx={cardItemWhyUsStyles.image}
            />
            <UiTypography variant="h5" sx={cardItemWhyUsStyles.title}>
              {t(item.title)}
            </UiTypography>
            <UiTypography
              variant="bodyText18"
              sx={cardItemWhyUsStyles.text}
              dangerouslySetInnerHTML={{ __html: t(item.text) || '' }}
            />
          </Box>
        </Box>
      )}
    </>
  );
}

export default CardItem;
