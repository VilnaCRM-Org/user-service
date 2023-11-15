import React from 'react';
import { Grid } from '@mui/material';

export function ForWhoSectionCards({ cardItemsJSX }: { cardItemsJSX: React.ReactNode; }) {
  return (
    <Grid container
          alignItems={'stretch'}
          spacing={3}
          sx={{
            position: 'absolute',
            bottom: '-150px',
            zIndex: 900,
            padding: '0 34px',
          }}>
      {cardItemsJSX}
    </Grid>
  );
}
