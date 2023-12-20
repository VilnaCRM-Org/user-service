import { Grid } from '@mui/material';
import React from 'react';

import { WHY_WE_CARD_ITEMS } from '../../../utils/constants/constants';
import CardItem from '../../CardItem/CardItem';

function CardList() {
  const styles = {
    grid: {
      display: 'grid',
      gridTemplateColumns: 'repeat(3,minmax(250px,390px))',
      marginTop: '40px',
      gap: '13px',
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
