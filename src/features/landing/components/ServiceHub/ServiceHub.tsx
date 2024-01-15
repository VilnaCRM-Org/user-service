import { Box, Container } from '@mui/material';
import React from 'react';

import { Cards } from './Cards';
import MainTitle from './MainTitle/MainTitle';
import { serviceHubStyles } from './styles';

function ServiceHub() {
  return (
    <Box id="ServiceHub" sx={serviceHubStyles.wrapper} component="section">
      <Container>
        <Box sx={serviceHubStyles.content}>
          <MainTitle />
          <Cards />
          <Box sx={serviceHubStyles.mainImage} />
        </Box>
      </Container>
      <Box sx={serviceHubStyles.line} />
    </Box>
  );
}

export default ServiceHub;
