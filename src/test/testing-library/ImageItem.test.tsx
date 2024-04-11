import { faker } from '@faker-js/faker';
import { render } from '@testing-library/react';
import React from 'react';

import { ImageItem } from '../../features/landing/components/Possibilities/ServicesHoverCard/ImageItem';
import { ImageList } from '../../features/landing/types/possibilities/image-list';

const item: ImageList = {
  image: faker.image.avatar(),
  alt: faker.lorem.sentence(),
};

describe('ImageItem component', () => {
  it('renders image with correct src and alt', () => {
    const { getByAltText } = render(<ImageItem item={item} />);

    expect(getByAltText(item.alt)).toBeInTheDocument();
  });
});
