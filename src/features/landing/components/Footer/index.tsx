import { useTheme } from '@emotion/react';
import { Stack, Box, Container, useMediaQuery } from '@mui/material';
import Image from 'next/image';
import React from 'react';

import Logo from '../../assets/svg/Logo/Logo.svg';

import CopyrightNoticeAndLinks from './CopyrightNoticeAndLinks/CopyrightNoticeAndLinks';
import Mobile from './Mobile/Mobile';
import PrivacyPolicy from './PrivacyPolicy/PrivacyPolicy';

function Footer() {
  const theme = useTheme();
  const tablet = useMediaQuery(theme.breakpoints.up('md'));

  return (
    // eslint-disable-next-line react/jsx-no-useless-fragment
    <>
      {tablet ? (
        <Stack
          sx={{
            borderTop: '1px solid  #E1E7EA',
            background: '#FFF',
            boxShadow: '0px -5px 46px 0px rgba(198, 209, 220, 0.25)',
          }}
        >
          <Box sx={{ paddingTop: '11px' }}>
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
            sx={{
              borderRadius: '16px 16px 0px 0px',
              background: '#F4F5F6',
              height: '61px',
            }}
          >
            <Container>
              <Stack
                direction="row"
                justifyContent="space-between"
                alignItems="center"
                ml="5px"
                mb="8px"
              >
                <CopyrightNoticeAndLinks />
              </Stack>
            </Container>
          </Stack>
        </Stack>
      ) : (
        <Mobile />
      )}
    </>
  );
}

export default Footer;
