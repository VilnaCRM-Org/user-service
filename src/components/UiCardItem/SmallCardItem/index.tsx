import { Stack } from '@mui/material';
import React from 'react';
import { Trans, useTranslation } from 'react-i18next';

import { DefaultImage } from '@/components/UiImage';
import UiTooltip from '@/components/UiTooltip';
import { DefaultTypography } from '@/components/UiTypography';

import { ServicesHoverCard } from '../../../features/landing/components/Possibilities/ServicesHoverCard';
import { CardItem, ImageItem } from '../types';

import styles from './styles';

interface SmallCardItem {
  item: CardItem;
  imageList: ImageItem[];
}
function SmallCardItem({ item, imageList }: SmallCardItem): React.ReactElement {
  const { t } = useTranslation();
  return (
    <Stack sx={styles.wrapper}>
      <DefaultImage src={item.imageSrc} alt={t(item.alt)} sx={styles.image} />
      <Stack flexDirection="column">
        <DefaultTypography variant="h6" sx={styles.title}>
          <Trans i18nKey={item.title} />
        </DefaultTypography>
        <DefaultTypography variant="bodyText16" sx={styles.text}>
          <Trans i18nKey={item.text}>
            Інтегруйте
            <UiTooltip
              placement="bottom"
              arrow
              sx={styles.hoveredCard}
              title={<ServicesHoverCard imageList={imageList} />}
            >
              <DefaultTypography variant="bodyText16">
                звичні сервіси
              </DefaultTypography>
            </UiTooltip>
            у кілька кліків
          </Trans>
        </DefaultTypography>
      </Stack>
    </Stack>
  );
}

export default SmallCardItem;
