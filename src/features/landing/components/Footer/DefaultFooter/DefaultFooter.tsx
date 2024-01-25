import { Container, Stack } from '@mui/material';
import Image from 'next/image';
import React from 'react';

import Logo from '../../../assets/svg/logo/Logo.svg';
import { ISocialMedia } from '../../../types/social-media';
import { CopyrightNoticeAndLinks } from '../CopyrightNoticeAndLinks';
import { PrivacyPolicy } from '../PrivacyPolicy';

import styles from './styles';

function DefaultFooter({ socialLinks }: { socialLinks: ISocialMedia[] }) {
  return (
    <Stack sx={styles.footerWrapper}>
      <Stack height="4.188rem" alignItems="center" flexDirection="row">
        <Container>
          <Stack
            direction="row"
            justifyContent="space-between"
            alignItems="center"
          >
            <Image src={Logo} alt="Logo" width={143} height={48} />
            <PrivacyPolicy />
          </Stack>
        </Container>
      </Stack>
      <Stack sx={styles.copyrightAndLinksWrapper}>
        <Container>
          <Stack sx={styles.copyrightAndLinks}>
            <CopyrightNoticeAndLinks socialLinks={socialLinks} />
          </Stack>
        </Container>
      </Stack>
    </Stack>
  );
}

export default DefaultFooter;
