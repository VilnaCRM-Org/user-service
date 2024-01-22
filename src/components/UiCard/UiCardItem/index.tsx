/* eslint-disable import/no-cycle */

import LargeCardItem from './LargeCardItem';
import SmallCardItem from './SmallCardItem';
import { ICardItem } from './types';

function UiCardItem({ item, type, imageList }: ICardItem) {
  return (
    <>
      {type === 'small' && imageList !== undefined && (
        <SmallCardItem item={item} imageList={imageList} />
      )}
      {type === 'large' && <LargeCardItem item={item} />}
    </>
  );
}

export default UiCardItem;
