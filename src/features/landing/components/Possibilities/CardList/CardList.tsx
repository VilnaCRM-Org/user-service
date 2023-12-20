import { Stack } from '@mui/material';
import React from 'react';

import { UNLIMITED_INTEGRATIONS_CARD_ITEMS } from '../../../utils/constants/constants';
import CardItem from '../CardItem/CardItem';

function CardList() {
  const styles = {
    grid: {
      display: 'flex',
      flexDirection: 'row',
      marginTop: '32px',
      gap: '12px',
    },
  };

  return (
    <Stack sx={{ ...styles.grid }}>
      {UNLIMITED_INTEGRATIONS_CARD_ITEMS.map(item => (
        <CardItem item={item} key={item.id} />
      ))}
    </Stack>
  );
}

export default CardList;
