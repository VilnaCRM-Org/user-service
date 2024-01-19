import { Container, Stack } from '@mui/material';
import Image from 'next/image';
import React from 'react';

import Logo from '../../../assets/svg/Logo/Logo.svg';
import { CopyrightNoticeAndLinks } from '../CopyrightNoticeAndLinks';
import { PrivacyPolicy } from '../PrivacyPolicy';

import { defaultFooterStyles } from './styles';

function DefaultFooter() {
  return (
    <Stack sx={defaultFooterStyles.footerWrapper} id="Contacts">
      <Stack height="67px" alignItems="center" flexDirection="row">
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
      <Stack sx={defaultFooterStyles.copyrightAndLinksWrapper}>
        <Container>
          <Stack sx={defaultFooterStyles.copyrightAndLinks}>
            <CopyrightNoticeAndLinks />
          </Stack>
        </Container>
      </Stack>
    </Stack>
  );
}

export default DefaultFooter;
