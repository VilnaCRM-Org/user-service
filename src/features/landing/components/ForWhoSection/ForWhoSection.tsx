import { Box, Container } from '@mui/material';
import React from 'react';

import { Cards } from './Cards';
import MainTitle from './MainTitle/MainTitle';
import { forWhoSectionStyles } from './styles';

function ForWhoSectionStyles() {
  return (
    <Box
      id="forWhoSectionStyles"
      component="section"
      sx={forWhoSectionStyles.wrapper}
    >
      <Container>
        <Box sx={forWhoSectionStyles.content}>
          <MainTitle />
          <Box sx={forWhoSectionStyles.lgCardsWrapper}>
            <Cards />
          </Box>
          <Box sx={forWhoSectionStyles.mainImage} />
        </Box>
      </Container>
      <Box sx={forWhoSectionStyles.smCardsWrapper}>
        <Cards />
      </Box>
      <Box sx={forWhoSectionStyles.line} />
    </Box>
  );
}

export default ForWhoSectionStyles;
