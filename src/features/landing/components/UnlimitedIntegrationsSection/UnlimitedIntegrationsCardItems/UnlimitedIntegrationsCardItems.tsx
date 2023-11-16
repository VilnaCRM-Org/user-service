import { useMemo } from 'react';
import { Grid } from '@mui/material';
import { IUnlimitedIntegrationsItem } from '@/features/landing/types/unlimited-integrations/types';
import { UnlimitedIntegrationsCardItem } from '@/features/landing/components/UnlimitedIntegrationsSection/UnlimitedIntegrationsCardItem/UnlimitedIntegrationsCardItem';

interface IUnlimitedIntegrationsCardItemsProps {
  cardItems: IUnlimitedIntegrationsItem[];
}

const styles = {
  mainGrid: {
    flexGrow: 1,
  },
};

export function UnlimitedIntegrationsCardItems({
  cardItems,
}: IUnlimitedIntegrationsCardItemsProps) {
  const cardItemsJSX = useMemo(() => {
    return cardItems.map((cardItem) => {
      return (
        <Grid key={cardItem.id} item xs={12} md={6} lg={3} sx={{ ...styles.mainGrid }}>
          <UnlimitedIntegrationsCardItem cardItem={cardItem} />
        </Grid>
      );
    });
  }, [cardItems]);

  return (
    <Grid container spacing={'12px'}>
      {[...cardItemsJSX]}
    </Grid>
  );
}
