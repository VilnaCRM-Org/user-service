import { Box } from '@mui/material';
import React from 'react';
import { Trans } from 'react-i18next';

import { UiTypography } from '../ui';

import { cardItemWhyUsStyles } from './styles';

function LargeCardItem({ item }: any) {
  return (
    <Box sx={cardItemWhyUsStyles.wrapper}>
      <Box key={item.id} sx={cardItemWhyUsStyles.content}>
        <Box
          style={{ backgroundImage: `url(${item.imageSrc.src})` }}
          sx={cardItemWhyUsStyles.image}
        />

        <UiTypography variant="h5" sx={cardItemWhyUsStyles.title}>
          <Trans i18nKey={item.title} />
        </UiTypography>
        <UiTypography variant="bodyText18" sx={cardItemWhyUsStyles.text}>
          <Trans i18nKey={item.text} />
        </UiTypography>
      </Box>
    </Box>
  );
}

export default LargeCardItem;
