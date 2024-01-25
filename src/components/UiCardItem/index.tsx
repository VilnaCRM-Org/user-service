/* eslint-disable import/no-cycle */

import LargeCardItem from './LargeCardItem';
import SmallCardItem from './SmallCardItem';
import { ICardItemProps } from './types';

function UiCardItem({ item, type, imageList }: ICardItemProps) {
  return (
    <>
      {type === 'small' && (
        <SmallCardItem item={item} imageList={imageList || []} />
      )}
      {type === 'large' && <LargeCardItem item={item} />}
    </>
  );
}

export default UiCardItem;
