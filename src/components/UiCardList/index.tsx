import CardGrid from './CardGrid';
import CardSwiper from './CardSwiper';
import { CardList } from './types';

function UiCardList({ cardList }: CardList): React.ReactElement {
  return (
    <>
      <CardGrid cardList={cardList} />
      <CardSwiper cardList={cardList} />
    </>
  );
}
export default UiCardList;
