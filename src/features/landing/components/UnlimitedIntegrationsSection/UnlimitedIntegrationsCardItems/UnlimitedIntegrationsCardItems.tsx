import { useMemo } from 'react';
import { Grid } from '@mui/material';
import { IUnlimitedIntegrationsItem } from '@/features/landing/types/unlimited-integrations/types';
import {
  UnlimitedIntegrationsCardItem,
} from '@/features/landing/components/UnlimitedIntegrationsSection/UnlimitedIntegrationsCardItem/UnlimitedIntegrationsCardItem';

interface IUnlimitedIntegrationsCardItemsProps {
  cardItems: IUnlimitedIntegrationsItem[];
}

export function UnlimitedIntegrationsCardItems({ cardItems }: IUnlimitedIntegrationsCardItemsProps) {
  const cardItemsJSX = useMemo(() => {
    return cardItems.map((cardItem) => {
      return <UnlimitedIntegrationsCardItem key={cardItem.id} cardItem={cardItem} />;
    });
  }, [cardItems]);

  return (
    <Grid container spacing={'12px'}>
      {[...cardItemsJSX]}
    </Grid>
  );
}
