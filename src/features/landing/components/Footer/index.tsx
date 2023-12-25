import { Stack, Box, Container } from '@mui/material';
import Image from 'next/image';
import React from 'react';

import Logo from '../../assets/svg/Logo/Logo.svg';

import CopyrightNoticeAndLinks from './CopyrightNoticeAndLinks/CopyrightNoticeAndLinks';
import PrivacyPolicy from './PrivacyPolicy/PrivacyPolicy';

function Footer() {
  return (
    <Stack
      sx={{
        borderTop: '1px solid  #E1E7EA)',
        background: '#FFF',
        boxShadow: '0px -5px 46px 0px rgba(198, 209, 220, 0.25)',
      }}
    >
      <Box sx={{ paddingTop: '11px', paddingBottom: '6px' }}>
        <Container
          sx={{
            display: 'flex',
            alignItems: 'center',
            direction: 'row',
            justifyContent: 'space-between',
          }}
        >
          <Image src={Logo} alt="Logo" width={131} height={44} />
          <PrivacyPolicy />
        </Container>
      </Box>
      <Box
        sx={{
          borderRadius: '16px 16px 0px 0px',
          background: '#F4F5F6',
          paddingTop: '11px',
          paddingBottom: '6px',
        }}
      >
        <Container
          sx={{
            display: 'flex',
            alignItems: 'center',
            direction: 'row',
            justifyContent: 'space-between',
          }}
        >
          <CopyrightNoticeAndLinks />
        </Container>
      </Box>
    </Stack>
  );
}

export default Footer;
