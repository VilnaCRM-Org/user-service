import { render } from '@testing-library/react';
import React from 'react';

import Logo from '../../../../assets/svg/logo/Logo.svg';
import { ImageList } from '../../../../types/possibilities/image-list';

import ImageItem from './ImageItem';

const item: ImageList = {
  image: Logo,
  alt: 'Test Alt Text',
};

const logoAlt: string = 'Test Alt Text';

describe('ImageItem component', () => {
  it('renders image with correct src and alt', () => {
    const { getByAltText } = render(<ImageItem item={item} />);

    expect(getByAltText(logoAlt)).toBeInTheDocument();
  });
});
