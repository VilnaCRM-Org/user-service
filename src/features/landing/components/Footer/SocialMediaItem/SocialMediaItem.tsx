import { Box } from '@mui/material';
import Image from 'next/image';
import Link from 'next/link';
import React from 'react';
import { useTranslation } from 'react-i18next';

import { ISocialMedia } from '../../../types/social-media';

import styles from './styles';

function SocialMediaItem({ item }: { item: ISocialMedia }): React.ReactElement {
  const { t } = useTranslation();
  const ariaLabel: string = t(item.ariaLabel);
  return (
    <Box sx={styles.navLink}>
      <Link href={item.linkHref} aria-label={ariaLabel}>
        <Image src={item.icon} alt={t(item.alt)} width={20} height={20} />
      </Link>
    </Box>
  );
}

export default SocialMediaItem;