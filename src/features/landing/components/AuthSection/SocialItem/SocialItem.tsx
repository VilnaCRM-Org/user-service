import { Button } from '@mui/material';
import Image from 'next/image';
import React from 'react';
import { useTranslation } from 'react-i18next';

import { UiTypography } from '@/components';

import { ISocialLink } from '../../../types/authentication/social';

import styles from './styles';

function SocialItem({ item }: { item: ISocialLink }) {
  const { t } = useTranslation();
  return (
    <Button sx={styles.itemWrapper}>
      <Image src={item.icon} alt={t(item.title)} width={22} height={22} />
      <UiTypography variant="demi18">{t(item.title)}</UiTypography>
    </Button>
  );
}

export default SocialItem;
