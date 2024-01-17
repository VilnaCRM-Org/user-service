import { Stack } from '@mui/material';
import Image from 'next/image';
import React from 'react';
import { Trans } from 'react-i18next';

import { HoverCard } from '../../features/landing/components/Possibilities/HoverCard';
import { UiTypography } from '../ui';
import { UiTooltip } from '../ui/UiTooltip';

import { cardItemPossibilitiesStyles } from './styles';
import styles from './styles.module.scss';

function SmallCarditem({ item, imageList }: any) {
  return (
    <Stack sx={cardItemPossibilitiesStyles.wrapper}>
      <Image
        src={item.imageSrc}
        alt="Card Image"
        width={80}
        height={80}
        className={styles.img}
      />
      <Stack flexDirection="column">
        <UiTypography variant="h6" sx={cardItemPossibilitiesStyles.title}>
          <Trans i18nKey={item.title} />
        </UiTypography>
        <UiTypography
          variant="bodyText16"
          sx={cardItemPossibilitiesStyles.text}
        >
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

export default SmallCarditem;
