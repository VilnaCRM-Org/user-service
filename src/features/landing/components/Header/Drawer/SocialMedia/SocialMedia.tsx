import { Box, Stack } from '@mui/material';
import Image from 'next/image';
import Link from 'next/link';
import React from 'react';
import { useTranslation } from 'react-i18next';

import { ISocialMedia } from '../../../../types/social-media';

import { socialMediaStyles } from './styles';

function SocialMedia({ socialMedia }: { socialMedia: ISocialMedia[] }) {
  const { t } = useTranslation();
  return (
    <Stack
      justifyContent="center"
      gap="8px"
      flexDirection="row"
      sx={socialMediaStyles.linkWrapper}
    >
      {socialMedia.map(({ icon, alt, id, linkHref, ariaLabel }) => (
        <Box key={id} m="12px">
          <Link href={linkHref} aria-label={t(ariaLabel) as string}>
            <Image src={icon} alt={t(alt)} width={24} height={24} key={id} />
          </Link>
        </Box>
      ))}
    </Stack>
  );
}

export default SocialMedia;
