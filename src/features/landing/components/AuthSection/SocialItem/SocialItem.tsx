import Image from 'next/image';
import React from 'react';
import { useTranslation } from 'react-i18next';

import { SocialShareBtn } from '@/components/UiButton';
import { DefaultTypography } from '@/components/UiTypography';

import { SocialLink } from '../../../types/authentication/social';

function SocialItem({ item }: { item: SocialLink }): React.ReactElement {
  const { t } = useTranslation();
  return (
    <SocialShareBtn name="socialButton">
      <Image src={item.icon} alt={t(item.title)} width={22} height={22} />
      <DefaultTypography variant="demi18" component="div">
        {t(item.title)}
      </DefaultTypography>
    </SocialShareBtn>
  );
}

export default SocialItem;
