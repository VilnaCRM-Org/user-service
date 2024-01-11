import { Stack } from '@mui/material';
import Image from 'next/image';
import React from 'react';
import { Trans, useTranslation } from 'react-i18next';

import { UiTypography } from '../ui';

import { cardItemPossibilitiesStyles } from './styles';

function SmallCarditem({ item }: any) {
  const { t } = useTranslation();
  return (
    <Stack sx={cardItemPossibilitiesStyles.wrapper}>
      <Image src={item.imageSrc} alt="Card Image" width={80} height={80} />
      <Stack flexDirection="column">
        <UiTypography variant="h6" sx={cardItemPossibilitiesStyles.title}>
          {t(item.title)}
        </UiTypography>
        <UiTypography
          variant="bodyText16"
          sx={cardItemPossibilitiesStyles.text}
        >
          <Trans i18nKey={item.text}>
            Інтегруйте <a href="/">звичні сервіси </a> у кілька кліків
          </Trans>
        </UiTypography>
      </Stack>
    </Stack>
  );
}

export default SmallCarditem;
