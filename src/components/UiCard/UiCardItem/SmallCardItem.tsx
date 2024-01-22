import { Stack } from '@mui/material';
import React from 'react';
import { Trans, useTranslation } from 'react-i18next';

// eslint-disable-next-line no-restricted-imports, import/no-cycle
import { HoverCard } from '@/features/landing/components/Possibilities/HoverCard';

import UiImage from '../../UiImage';
import UiTooltip from '../../UiTooltip';
import UiTypography from '../../UiTypography';

import { smallCarditemStyles } from './styles';

interface CardItem {
  item: {
    id: string;
    imageSrc: string;
    title: string;
    text: string;
    alt: string;
  };
  imageList: {
    alt: string;
    image: string;
  }[];
}
function SmallCardItem({ item, imageList }: CardItem) {
  const { t } = useTranslation();
  return (
    <Stack sx={smallCarditemStyles.wrapper}>
      <UiImage
        src={item.imageSrc}
        alt={t(item.alt)}
        sx={smallCarditemStyles.image}
      />
      <Stack flexDirection="column">
        <UiTypography variant="h6" sx={smallCarditemStyles.title}>
          <Trans i18nKey={item.title} />
        </UiTypography>
        <UiTypography variant="bodyText16" sx={smallCarditemStyles.text}>
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
