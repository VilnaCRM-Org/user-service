import { Box, Stack } from '@mui/material';
import React from 'react';
import { useTranslation } from 'react-i18next';

import { UiTypography } from '@/components/';

import { ImageList } from '../../../types/possibilities/image-list';

import { ImageItem } from './ImageItem';
import styles from './styles';

function ServicesHoverCard({
  imageList,
}: {
  imageList: ImageList[];
}): React.ReactElement {
  const { t } = useTranslation();
  return (
    <Box>
      <UiTypography variant="demi18">
        {t('unlimited_possibilities.service_text.title')}
      </UiTypography>
      <UiTypography variant="medium14" sx={styles.text}>
        {t('unlimited_possibilities.service_text.text')}
      </UiTypography>
      <Stack sx={styles.listWrapper}>
        {imageList.map(item => (
          <ImageItem item={item} key={item.alt} />
        ))}
      </Stack>
    </Box>
  );
}

export default ServicesHoverCard;
