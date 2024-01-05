import { Box } from '@mui/material';
import React from 'react';

import CardList from './CardList/CardList';
import { RegistrationText } from './RegistrationText';

function Possibilities() {
  return (
    <Box sx={{ mt: '7rem', pb: '56px' }}>
      <RegistrationText />
      <CardList />
    </Box>
  );
}

export default Possibilities;
