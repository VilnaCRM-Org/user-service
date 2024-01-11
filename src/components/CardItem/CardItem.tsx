import React from 'react';

import LargeCardItem from './LargeCardItem';
import SmallCarditem from './SmallCarditem';

interface CardItemInterface {
  item: {
    id: string;
    imageSrc: string;
    title: string;
    text: string;
    width?: number;
    height?: number;
  };
  type: 'large' | 'small';
}

function CardItem({ item, type }: CardItemInterface) {
  return (
    <>
      {type === 'small' && <SmallCarditem item={item} />}
      {type === 'large' && <LargeCardItem item={item} />}
    </>
  );
}

export default CardItem;
