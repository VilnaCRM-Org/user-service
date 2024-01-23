import { Stack } from '@mui/material';
import React from 'react';
import { Trans, useTranslation } from 'react-i18next';

// eslint-disable-next-line no-restricted-imports, import/no-cycle
import { HoverCard } from '@/features/landing/components/Possibilities/HoverCard';

import UiImage from '../../../UiImage';
import UiTooltip from '../../../UiTooltip';
import UiTypography from '../../../UiTypography';
import { CardItem, ImageItem } from '../types';

import { styles } from './styles';

interface SmallCardItem {
  item: CardItem;
  imageList: ImageItem[];
}
function SmallCardItem({ item, imageList }: SmallCardItem) {
  const { t } = useTranslation();
  return (
    <Stack sx={styles.wrapper}>
      <UiImage src={item.imageSrc} alt={t(item.alt)} sx={styles.image} />
      <Stack flexDirection="column">
        <UiTypography variant="h6" sx={styles.title}>
          <Trans i18nKey={item.title} />
        </UiTypography>
        <UiTypography variant="bodyText16" sx={styles.text}>
          <Trans i18nKey={item.text}>
            Інтегруйте
            <UiTooltip
              placement="bottom"
              content={<HoverCard imageList={imageList} />}
            >
              звичні сервіси
            </UiTooltip>
            у кілька кліків
          </Trans>
        </UiTypography>
      </Stack>
    </Stack>
  );
}

export default SmallCardItem;
