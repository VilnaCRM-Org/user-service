import { Grid } from '@mui/material';
import React from 'react';

import { CardItem } from '@/components/CardItem';

import { UNLIMITED_INTEGRATIONS_CARD_ITEMS } from '../../../utils/constants/constants';

import { cardListStyles } from './styles';

function CardList() {
  return (
    <Grid sx={cardListStyles.grid}>
      {UNLIMITED_INTEGRATIONS_CARD_ITEMS.map(item => (
        <CardItem item={item} key={item.id} type="Possibilities" />
      ))}
    </Grid>
  );
}

export default CardList;
