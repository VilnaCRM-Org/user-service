/* eslint-disable react/require-default-props */
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
  imageList?: {
    alt: string;
    image: string;
  }[];
  type: 'large' | 'small';
}

function CardItem({ item, type, imageList }: CardItemInterface) {
  return (
    <>
      {type === 'small' && <SmallCarditem item={item} imageList={imageList} />}
      {type === 'large' && <LargeCardItem item={item} />}
    </>
  );
}

export default CardItem;
