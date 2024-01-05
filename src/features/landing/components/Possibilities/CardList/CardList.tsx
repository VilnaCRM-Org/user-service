import { Grid } from '@mui/material';
import React from 'react';

import { CardItem } from '@/components/CardItem';

import { UNLIMITED_INTEGRATIONS_CARD_ITEMS } from '../../../utils/constants/constants';

function CardList() {
  const styles = {
    grid: {
      display: 'grid',
      gridTemplateColumns: { md: 'repeat(2, 1fr)', lg: 'repeat(4, 1fr)' },
      marginTop: '2rem',
      gap: '0.75rem',
    },
  };

  return (
    <Grid sx={{ ...styles.grid }}>
      {UNLIMITED_INTEGRATIONS_CARD_ITEMS.map(item => (
        <CardItem item={item} key={item.id} type="Possibilities" />
      ))}
    </Grid>
  );
}

export default CardList;
