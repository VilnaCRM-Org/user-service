import { Stack, Box, Container } from '@mui/material';
import Image from 'next/image';
import React from 'react';

import Logo from '../../assets/svg/Logo/Logo.svg';

import { CopyrightNoticeAndLinks } from './CopyrightNoticeAndLinks';
import { PrivacyPolicy } from './PrivacyPolicy';
import { footerStyles } from './styles';

function Footer() {
  return (
    <Stack sx={footerStyles.footerWrapper}>
      <Box paddingTop="11px">
        <Container>
          <Stack
            direction="row"
            justifyContent="space-between"
            alignItems="center"
            pb="7px"
          >
            <Image src={Logo} alt="Logo" width={143} height={49} />
            <PrivacyPolicy />
          </Stack>
        </Container>
      </Box>
      <Stack
        alignItems="center"
        direction="row"
        sx={footerStyles.copyrightAndLinksWrapper}
      >
        <Container>
          <Stack
            direction="row"
            justifyContent="space-between"
            alignItems="center"
            ml="5px"
            mb="4px"
          >
            <CopyrightNoticeAndLinks />
          </Stack>
        </Container>
      </Stack>
    </Stack>
  );
}

export default Footer;
