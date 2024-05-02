import { Stack } from '@mui/material';
import React from 'react';
import { useTranslation } from 'react-i18next';

import UiImage from '@/components/UiImage';

import CardContent from './CardContent';
import styles from './styles';
import { UiCardItemProps } from './types';

function UiCardItem({ item }: UiCardItemProps): React.ReactElement {
  const { t } = useTranslation();

  const isSmallCard: boolean = item.type === 'smallCard';

  return (
    <Stack sx={isSmallCard ? styles.smallWrapper : styles.largeWrapper}>
      <UiImage
        src={item.imageSrc}
        alt={t(item.alt)}
        sx={isSmallCard ? styles.smallImage : styles.largeImage}
      />
      <Stack flexDirection="column">
        <CardContent item={item} isSmallCard={isSmallCard} />
      </Stack>
    </Stack>
  );
}
export default UiCardItem;
