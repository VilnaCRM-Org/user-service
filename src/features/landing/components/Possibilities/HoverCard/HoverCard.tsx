import { Box, Stack } from '@mui/material';
import Image from 'next/image';
import React from 'react';
import { useTranslation } from 'react-i18next';

import { UiTypography } from '@/components';

import { IimageList } from '../../../types/possibilities/image-list';

import styles from './styles';

function HoverCard({ imageList }: { imageList: IimageList[] }) {
  const { t } = useTranslation();
  return (
    <Box maxWidth="282px">
      <UiTypography variant="demi18">
        {t('unlimited_possibilities.service_text.title')}
      </UiTypography>
      <UiTypography variant="medium14" sx={styles.text}>
        {t('unlimited_possibilities.service_text.text')}
      </UiTypography>
      <Stack flexDirection="row" flexWrap="wrap" gap="30px">
        {imageList.map(item => (
          <Image
            src={item.image}
            alt={item.alt}
            width={45}
            height={45}
            key={item.alt}
          />
        ))}
      </Stack>
    </Box>
  );
}

export default HoverCard;
