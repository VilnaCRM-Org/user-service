import { Box } from '@mui/material';
import React from 'react';
import { Trans } from 'react-i18next';

import UiImage from '../UiImage';
import UiTypography from '../UiTypography';

import { largeCardItemStyles } from './styles';

function LargeCardItem({ item }: any) {
  return (
    <Box sx={largeCardItemStyles.wrapper}>
      <Box key={item.id} sx={largeCardItemStyles.content}>
        <UiImage
          src={item.imageSrc}
          alt="Card Image"
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
