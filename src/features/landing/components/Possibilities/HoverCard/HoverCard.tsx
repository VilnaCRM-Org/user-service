import { Box, Stack } from '@mui/material';
import Image from 'next/image';
import React from 'react';
import { useTranslation } from 'react-i18next';

import { UiTypography } from '@/components/ui';

function HoverCard({ imageList }: any) {
  const { t } = useTranslation();
  return (
    <Box maxWidth="282px">
      <UiTypography variant="demi18">
        {t('unlimited_possibilities.service_text.title')}
      </UiTypography>
      <UiTypography variant="medium14" sx={{ pt: '4px', pb: '22px' }}>
        {t('unlimited_possibilities.service_text.text')}
      </UiTypography>
      <Stack flexDirection="row" flexWrap="wrap" gap="30px">
        {imageList.map((item: { image: string; alt: string }) => (
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
