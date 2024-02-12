import Image from 'next/image';
import React from 'react';

import { ImageList } from '../../../../types/possibilities/image-list';

function ImageItem({ item }: { item: ImageList }): React.ReactElement {
  return (
    <Image
      src={item.image}
      alt={item.alt}
      width={45}
      height={45}
      key={item.alt}
    />
  );
}

export default ImageItem;
