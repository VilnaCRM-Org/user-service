import { Box, Container } from '@mui/material';
import Image from 'next/image';
import React from 'react';

import VectorIcon from '../../assets/img/about-vilna/FrameDesktop.png';

import { Cards } from './Cards';
import MainTitle from './MainTitle/MainTitle';
import styles from './styles';

function ForWhoSection(): React.ReactElement {
  return (
    <Box id="forWhoSection" component="section" sx={styles.wrapper}>
      <Container>
        <Box sx={styles.content}>
          <MainTitle />
          <Box sx={styles.lgCardsWrapper}>
            <Cards />
          </Box>

          <Box sx={styles.mainImage}>
            <Image src={VectorIcon} alt="vector" width={800} height={500} />
          </Box>
        </Box>
      </Container>
      <Box sx={styles.smCardsWrapper}>
        <Cards />
      </Box>
      <Box sx={styles.line} />
    </Box>
  );
}

export default ForWhoSection;
