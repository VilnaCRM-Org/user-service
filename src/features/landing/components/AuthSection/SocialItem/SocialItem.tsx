import { Stack } from '@mui/material';
import Image from 'next/image';
import React from 'react';
import { useTranslation } from 'react-i18next';

import { UiTypography } from '@/components';

import { ISocialLink } from '../../../types/authentication/social';

import { socialItemStyles } from './styles';

function SocialItem({ item }: { item: ISocialLink }) {
  const { t } = useTranslation();
  return (
    <Stack
      direction="row"
      gap="9px"
      alignItems="center"
      justifyContent="center"
      sx={socialItemStyles.itemWrapper}
    >
      <Image src={item.icon} alt={t(item.title)} width={22} height={22} />
      <UiTypography variant="demi18">{t(item.title)}</UiTypography>
    </Stack>
  );
}

export default SocialItem;
