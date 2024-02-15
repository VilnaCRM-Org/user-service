import { Box } from '@mui/material';
import React from 'react';
import { Trans, useTranslation } from 'react-i18next';

import { DefaultImage } from '@/components/UiImage';
import { DefaultTypography } from '@/components/UiTypography';

import { CardItem } from '../types';

import styles from './styles';

function LargeCardItem({ item }: { item: CardItem }): React.ReactElement {
  const { t } = useTranslation();
  return (
    <Box sx={styles.wrapper}>
      <Box key={item.id} sx={styles.content}>
        <DefaultImage src={item.imageSrc} alt={t(item.alt)} sx={styles.image} />
        <DefaultTypography variant="h5" sx={styles.title}>
          <Trans i18nKey={item.title} />
        </DefaultTypography>
        <DefaultTypography variant="bodyText18" sx={styles.text}>
          <Trans i18nKey={item.text} />
        </DefaultTypography>
      </Box>
    </Box>
  );
}

export default LargeCardItem;
