import { render } from '@testing-library/react';
import React from 'react';

import { SocialLink } from '../../../types/authentication/social';

import SocialItem from './SocialItem';

const item: SocialLink = {
  id: '1',
  linkHref: 'http://example.com',
  title: 'Example Link',
  icon: '',
};

describe('SocialItem', () => {
  test('renders social item with correct title and icon', () => {
    const { getByAltText, getByText } = render(<SocialItem item={item} />);

    const titleElement: HTMLElement = getByText(item.title);
    expect(titleElement).toBeInTheDocument();

    const imageElement: HTMLElement = getByAltText(item.title);
    expect(imageElement).toBeInTheDocument();

    expect(imageElement).toHaveAttribute('src', item.icon);
  });
});
