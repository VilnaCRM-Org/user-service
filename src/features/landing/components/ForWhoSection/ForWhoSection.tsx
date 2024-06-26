import { Box, Container } from '@mui/material';
import React from 'react';

import VectorIcon from '../../assets/img/about-vilna/FrameDesktop.png';
import VectorIconMd from '../../assets/img/about-vilna/FrameTablet.png';

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
            <img
              src={VectorIcon}
              srcSet={`${VectorIconMd.src} 689w, ${VectorIcon.src} 821w`}
              sizes="(max-width: 1130.98px) 680px, 800px"
              alt="vector"
              width={800}
              height={498}
            />
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
