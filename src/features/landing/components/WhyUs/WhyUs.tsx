import { Box } from '@mui/material';
import React from 'react';

import UiCardList from '@/components/UiCard/UiCardList';

import { cardList } from './dataArray';
import { Heading } from './Heading';
import { whyUsStyles } from './styles';

function WhyUs() {
  return (
    <Box sx={whyUsStyles.wrapper} id="Advantages" component="section">
      <Heading />
      <UiCardList cardList={cardList} type="large" />
    </Box>
  );
}

export default WhyUs;
