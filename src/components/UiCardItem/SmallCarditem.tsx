import { Stack } from '@mui/material';
import React from 'react';
import { Trans } from 'react-i18next';

import { HoverCard } from '../../features/landing/components/Possibilities/HoverCard';
import UiImage from '../UiImage';
import UiTooltip from '../UiTooltip';
import UiTypography from '../UiTypography';

import { smallCarditemStyles } from './styles';

function SmallCarditem({ item, imageList }: any) {
  return (
    <Stack sx={smallCarditemStyles.wrapper}>
      <UiImage
        src={item.imageSrc}
        alt="Card Image"
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

export default SmallCarditem;
