import { Box } from '@mui/material';
import React from 'react';
import { Trans, useTranslation } from 'react-i18next';

import { UiTypography } from '@/components/';
import UiImage from '@/components/UiImage';

import { CardItem } from '../types';

import styles from './styles';

function LargeCardItem({ item }: { item: CardItem }): React.ReactElement {
  const { t } = useTranslation();
  return (
    <Box sx={styles.wrapper}>
      <Box key={item.id} sx={styles.content}>
        <UiImage src={item.imageSrc} alt={t(item.alt)} sx={styles.image} />
        <UiTypography variant="h5" sx={styles.title}>
          <Trans i18nKey={item.title} />
        </UiTypography>
        <UiTypography variant="bodyText18" sx={styles.text}>
          <Trans i18nKey={item.text} />
        </UiTypography>
      </Box>
    </Box>
  );
}

export default LargeCardItem;
