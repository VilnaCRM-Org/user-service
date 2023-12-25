import { Stack } from '@mui/material';
import React from 'react';

import CardItem from '@/components/CardItem/CardItem';

import { UNLIMITED_INTEGRATIONS_CARD_ITEMS } from '../../../utils/constants/constants';

function CardList() {
  const styles = {
    grid: {
      display: 'flex',
      flexDirection: 'row',
      marginTop: '2rem',
      gap: '0.75rem',
    },
  };

  return (
    <Stack sx={{ ...styles.grid }}>
      {UNLIMITED_INTEGRATIONS_CARD_ITEMS.map(item => (
        <CardItem item={item} key={item.id} type="Possibilities" />
      ))}
    </Stack>
  );
}

export default CardList;
