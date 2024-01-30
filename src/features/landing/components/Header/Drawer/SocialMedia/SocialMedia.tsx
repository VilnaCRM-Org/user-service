import { Box, Stack } from '@mui/material';
import Image from 'next/image';
import Link from 'next/link';
import React from 'react';
import { useTranslation } from 'react-i18next';

import { ISocialMedia } from '../../../../types/social-media';

import styles from './styles';

function SocialMedia({
  socialMedia,
}: {
  socialMedia: ISocialMedia[];
}): React.ReactElement {
  const { t } = useTranslation();
  return (
    <Stack
      justifyContent="center"
      gap="0.5rem"
      flexDirection="row"
      sx={styles.linkWrapper}
    >
      {socialMedia.map(({ icon, alt, id, linkHref, ariaLabel }) => (
        <Box key={id} m="0.75rem">
          <Link href={linkHref} aria-label={t(ariaLabel) as string}>
            <Image src={icon} alt={t(alt)} width={24} height={24} key={id} />
          </Link>
        </Box>
      ))}
    </Stack>
  );
}

export default SocialMedia;
