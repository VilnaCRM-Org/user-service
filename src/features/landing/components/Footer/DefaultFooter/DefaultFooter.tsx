import { Box, Stack } from '@mui/material';
import Image from 'next/image';
import React from 'react';

import Logo from '../../../assets/svg/logo/Logo.svg';
import { ISocialMedia } from '../../../types/social-media';
import { CopyrightNoticeAndLinks } from '../CopyrightNoticeAndLinks';
import { PrivacyPolicy } from '../PrivacyPolicy';

import styles from './styles';

function DefaultFooter({
  socialLinks,
}: {
  socialLinks: ISocialMedia[];
}): React.ReactElement {
  return (
    <Stack sx={styles.footerWrapper}>
      <Stack height="4.188rem" alignItems="center" flexDirection="row">
        <Box sx={styles.topWrapper}>
          <Stack
            direction="row"
            justifyContent="space-between"
            alignItems="center"
          >
            <Image src={Logo} alt="Logo" width={143} height={48} />
            <PrivacyPolicy />
          </Stack>
        </Box>
      </Stack>
      <Stack sx={styles.copyrightAndLinksWrapper}>
        <Box sx={styles.bottomWrapper}>
          <Stack sx={styles.copyrightAndLinks}>
            <CopyrightNoticeAndLinks socialLinks={socialLinks} />
          </Stack>
        </Box>
      </Stack>
    </Stack>
  );
}

export default DefaultFooter;