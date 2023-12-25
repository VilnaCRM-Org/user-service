import { Grid } from '@mui/material';
import React from 'react';

import CardItem from '../../../../../components/CardItem/CardItem';
import { WHY_WE_CARD_ITEMS } from '../../../utils/constants/constants';

function CardList() {
  const styles = {
    grid: {
      display: 'grid',
      gridTemplateColumns: 'repeat(3,minmax(15.625rem, 24.375rem))',
      marginTop: '2.5rem',
      gap: '0.813rem',
    },
  };

  return (
    <Grid sx={{ ...styles.grid }}>
      {WHY_WE_CARD_ITEMS.map(item => (
        <CardItem item={item} key={item.id} type="WhyUs" />
      ))}
    </Grid>
  );
}

export default CardList;
