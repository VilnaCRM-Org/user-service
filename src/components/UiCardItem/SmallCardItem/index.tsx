import { Stack } from '@mui/material';
import React from 'react';
import { Trans, useTranslation } from 'react-i18next';

import { UiImage, UiTypography } from '@/components';
import { ServicesTooltip } from '@/components/UiTooltip';

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
      <UiImage src={item.imageSrc} alt={t(item.alt)} sx={styles.image} />
      <Stack flexDirection="column">
        <UiTypography variant="h6" sx={styles.title}>
          <Trans i18nKey={item.title} />
        </UiTypography>
        <UiTypography variant="bodyText16" sx={styles.text}>
          <Trans i18nKey={item.text}>
            Інтегруйте
            <ServicesTooltip
              placement="bottom"
              arrow
              title={<ServicesHoverCard imageList={imageList} />}
            >
              <UiTypography>звичні сервіси</UiTypography>
            </ServicesTooltip>
            у кілька кліків
          </Trans>
        </UiTypography>
      </Stack>
    </Stack>
  );
}

export default SmallCardItem;
