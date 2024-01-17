import { Stack, Container } from '@mui/material';
import Image from 'next/image';
import React from 'react';

import Logo from '../../assets/svg/Logo/Logo.svg';

import Adaptive from './Adaptive/Mobile';
import { CopyrightNoticeAndLinks } from './CopyrightNoticeAndLinks';
import { PrivacyPolicy } from './PrivacyPolicy';
import { footerStyles } from './styles';

function Footer() {
  return (
    <>
      <Stack sx={footerStyles.footerWrapper} id="Contacts">
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
        <Stack sx={footerStyles.copyrightAndLinksWrapper}>
          <Container>
            <Stack sx={footerStyles.copyrightAndLinks}>
              <CopyrightNoticeAndLinks />
            </Stack>
          </Container>
        </Stack>
      </Stack>
      <Adaptive />
    </>
  );
}

export default Footer;
