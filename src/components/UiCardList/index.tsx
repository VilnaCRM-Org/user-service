import { Box } from '@mui/material';

import CardGrid from './CardGrid';
import CardSwiper from './CardSwiper';
import styles from './styles';
import { CardList } from './types';

function UiCardList({ cardList }: CardList): React.ReactElement {
  return (
    <>
      <Box sx={styles.gridContainerLargeScreen}>
        <CardGrid cardList={cardList} />
      </Box>
      <Box sx={styles.swiperContainerSmallScreen}>
        <CardSwiper cardList={cardList} />
      </Box>
    </>
  );
}

export default UiCardList;
