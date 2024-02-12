import LargeCardList from './LargeCardList';
import SmallCardList from './SmallCardList';
import { CardList } from './types';

function UCardList({
  type,
  imageList,
  cardList,
}: CardList): React.ReactElement {
  return (
    <>
      {type === 'small' && (
        <SmallCardList imageList={imageList} cardList={cardList} />
      )}
      {type === 'large' && <LargeCardList cardList={cardList} />}
    </>
  );
}

export default UCardList;
