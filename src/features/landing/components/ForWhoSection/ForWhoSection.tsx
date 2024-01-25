import { Box, Container } from '@mui/material';
import React from 'react';

import { Cards } from './Cards';
import MainTitle from './MainTitle/MainTitle';
import styles from './styles';

function ForWhoSectionStyles() {
  return (
    <Box id="forWhoSectionStyles" component="section" sx={styles.wrapper}>
      <Container>
        <Box sx={styles.content}>
          <MainTitle />
          <Box sx={styles.lgCardsWrapper}>
            <Cards />
          </Box>
          <Box sx={styles.mainImage} />
        </Box>
      </Container>
      <Box sx={styles.smCardsWrapper}>
        <Cards />
      </Box>
      <Box sx={styles.line} />
    </Box>
  );
}

export default ForWhoSectionStyles;
