import { Box } from '@mui/material';
import React from 'react';

import CardList from './CardList/CardList';
import { WhyUsText } from './WhyUsText';

function WhyUs() {
  return (
    <Box mt="112px">
      <WhyUsText />
      <CardList />
    </Box>
  );
}

export default WhyUs;
