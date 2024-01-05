import { Stack, Box, Container } from '@mui/material';
import Image from 'next/image';
import React from 'react';

import Logo from '../../assets/svg/Logo/Logo.svg';

import { CopyrightNoticeAndLinks } from './CopyrightNoticeAndLinks';
import { PrivacyPolicy } from './PrivacyPolicy';
import styles from './Styles.module.scss';

function Footer() {
  return (
    <Stack className={styles.wrapper}>
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
        className={styles.copyrightAndLinksWrapper}
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
  );
}

export default Footer;
