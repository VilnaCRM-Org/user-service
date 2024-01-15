import { Box } from '@mui/material';
import React from 'react';

import CardList from './CardList/CardList';
import { RegistrationText } from './RegistrationText';
import { possibilitiesStyles } from './styles';

function Possibilities() {
  return (
    <Box sx={possibilitiesStyles.wrapper} id="Integration">
      <RegistrationText />
      <CardList />
    </Box>
  );
}

export default Possibilities;
