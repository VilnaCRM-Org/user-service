import { useMediaQuery } from '@mui/system';

import CardGrid from './CardGrid';
import CardSwiper from './CardSwiper';
import { CardList } from './types';

function UiCardList({ cardList }: CardList): React.ReactElement {
  const isLarge: boolean = useMediaQuery('(min-width:640px)');
  return isLarge ? (
    <CardGrid cardList={cardList} />
  ) : (
    <CardSwiper cardList={cardList} />
  );
}

export default UiCardList;
