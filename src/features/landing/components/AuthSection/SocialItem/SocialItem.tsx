import Image from 'next/image';
import React from 'react';
import { useTranslation } from 'react-i18next';

import { UiButton, UiTypography } from '@/components';

import { ISocialLink } from '../../../types/authentication/social';

function SocialItem({ item }: { item: ISocialLink }) {
  const { t } = useTranslation();
  return (
    <UiButton variant="outlined" size="medium" name="socialButton">
      <Image src={item.icon} alt={t(item.title)} width={22} height={22} />
      <UiTypography variant="demi18" component="div">
        {t(item.title)}
      </UiTypography>
    </UiButton>
  );
}

export default SocialItem;
