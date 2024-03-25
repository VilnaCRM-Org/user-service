import { Grid } from '@mui/material';
import { CSSProperties } from 'react';

import UiCardItem from '../UiCardItem';

import styles from './styles';
import { CardList } from './types';

function CardGrid({ cardList }: CardList): React.ReactElement {
  const grid: CSSProperties =
    cardList[0].type === 'smallCard' ? styles.smallGrid : styles.largeGrid;

  return (
    <Grid sx={grid}>
      {cardList.map(item => (
        <UiCardItem key={item.id} item={item} />
      ))}
    </Grid>
  );
}
export default CardGrid;
