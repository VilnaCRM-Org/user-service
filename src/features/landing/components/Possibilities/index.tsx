import { Box } from '@mui/material';
import React from 'react';

import CardList from './CardList/CardList';
import Text from './Text/Text';

function Possibilities() {
  return (
    <Box sx={{ mt: '7rem' }}>
      <Text />
      <CardList />
    </Box>
  );
}

export default Possibilities;
