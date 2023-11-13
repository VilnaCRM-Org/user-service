import { Grid } from '@mui/material';
import { IWhyWeCardItem } from '@/features/landing/types/why-we/types';
import { WhyWeSectionCardItem } from '../WhyWeSectionCardItem/WhyWeSectionCardItem';

interface IWhyWeSectionCardItemsRowProps {
  cardItems: IWhyWeCardItem[];
  style?: React.CSSProperties;
}

export function WhyWeSectionCardItemsRow({ cardItems, style }: IWhyWeSectionCardItemsRowProps) {
  return (
    <Grid container
          sx={{
            ...style,
          }}
          spacing={'13px'}>
      {
        cardItems.map(cardItem => {
          return <Grid item key={cardItem.id} xs={4}>
            <WhyWeSectionCardItem cardItem={cardItem} />
          </Grid>;
        })
      }
    </Grid>
  );
}
