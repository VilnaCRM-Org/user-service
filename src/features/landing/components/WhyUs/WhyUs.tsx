import { Box } from '@mui/material';
import React from 'react';

import UiCardList from '@/components/UiCardList';

import { cardList } from './dataArray';
import { Heading } from './Heading';
import styles from './styles';

function WhyUs() {
  return (
    <Box sx={styles.wrapper} id="Advantages" component="section">
      <Heading />
      <UiCardList cardList={cardList} type="large" />
    </Box>
  );
}

export default WhyUs;
