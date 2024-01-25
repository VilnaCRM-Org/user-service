/* eslint-disable react/require-default-props */
import LargeCardList from './LargeCardList';
import SmallCardList from './SmallCardList';
import { ICardList } from './types';

function UiCardList({ type, imageList, cardList }: ICardList) {
  return (
    <>
      {type === 'small' && (
        <SmallCardList imageList={imageList} cardList={cardList} />
      )}
      {type === 'large' && <LargeCardList cardList={cardList} />}
    </>
  );
}

export default UiCardList;
