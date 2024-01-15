import { Box } from '@mui/material';
import React from 'react';

import CardList from './CardList/CardList';
import { whyUsStyles } from './styles';
import { WhyUsText } from './WhyUsText';

function WhyUs() {
  return (
    <Box sx={whyUsStyles.wrapper} id="Advantages" pb="145px">
      <WhyUsText />
      <CardList />
    </Box>
  );
}

export default WhyUs;
