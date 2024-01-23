import { Box } from '@mui/material';
import React from 'react';

import UiCardList from '@/components/UiCard/UiCardList';

import { cardList, imageList } from './dataArray';
import { RegistrationText } from './RegistrationText';
import { possibilitiesStyles } from './styles';

function Possibilities() {
  return (
    <Box sx={possibilitiesStyles.wrapper} id="Integration" component="section">
      <RegistrationText />
      <UiCardList imageList={imageList} cardList={cardList} type="small" />
    </Box>
  );
}

export default Possibilities;
