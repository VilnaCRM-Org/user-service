import { Stack } from '@mui/material';
import Image from 'next/image';
import React from 'react';
import { Trans } from 'react-i18next';

import Services from '../../features/landing/components/Possibilities/Services/Services';
import { UiTypography } from '../ui';

import { cardItemPossibilitiesStyles } from './styles';

function SmallCarditem({ item }: any) {
  return (
    <Stack sx={cardItemPossibilitiesStyles.wrapper}>
      <Image src={item.imageSrc} alt="Card Image" width={80} height={80} />
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
            <Services>звичні сервіси</Services>у кілька кліків
          </Trans>
        </UiTypography>
      </Stack>
    </Stack>
  );
}

export default SmallCarditem;
