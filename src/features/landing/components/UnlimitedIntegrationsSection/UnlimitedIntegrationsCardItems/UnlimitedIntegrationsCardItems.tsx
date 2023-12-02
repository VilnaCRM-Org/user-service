import { Grid } from '@mui/material';
import { useMemo } from 'react';

import IUnlimitedIntegrationsItem from '../../../types/unlimited-integrations/types';
import UnlimitedIntegrationsCardItem from '../UnlimitedIntegrationsCardItem/UnlimitedIntegrationsCardItem';

interface IUnlimitedIntegrationsCardItemsProps {
  cardItems: IUnlimitedIntegrationsItem[];
}

const styles = {
  mainGrid: {
    flexGrow: 1,
  },
};

export default function UnlimitedIntegrationsCardItems({
  cardItems,
}: IUnlimitedIntegrationsCardItemsProps) {
  const cardItemsJSX = useMemo(
    () =>
      cardItems.map((cardItem) => (
        <Grid key={cardItem.id} item xs={12} md={6} lg={3} sx={{ ...styles.mainGrid }}>
          <UnlimitedIntegrationsCardItem cardItem={cardItem} />
        </Grid>
      )),
    [cardItems]
  );

  return (
    <Grid container spacing="12px" sx={{ padding: '0 10px' }}>
      {[...cardItemsJSX]}
    </Grid>
  );
}
