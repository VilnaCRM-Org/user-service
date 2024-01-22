import { Box } from '@mui/material';
import React from 'react';
import { Trans, useTranslation } from 'react-i18next';

import UiImage from '../../UiImage';
import UiTypography from '../../UiTypography';

import { largeCardItemStyles } from './styles';

type CardItem = {
  item: {
    id: string;
    imageSrc: string;
    title: string;
    text: string;
    alt: string;
  };
};
function LargeCardItem({ item }: CardItem) {
  const { t } = useTranslation();
  return (
    <Box sx={largeCardItemStyles.wrapper}>
      <Box key={item.id} sx={largeCardItemStyles.content}>
        <UiImage
          src={item.imageSrc}
          alt={t(item.alt)}
          sx={largeCardItemStyles.image}
        />
        <UiTypography variant="h5" sx={largeCardItemStyles.title}>
          <Trans i18nKey={item.title} />
        </UiTypography>
        <UiTypography variant="bodyText18" sx={largeCardItemStyles.text}>
          <Trans i18nKey={item.text} />
        </UiTypography>
      </Box>
    </Box>
  );
}

export default LargeCardItem;
