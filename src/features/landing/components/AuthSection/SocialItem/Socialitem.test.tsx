import { render } from '@testing-library/react';
import React from 'react';

import { testSocialLink } from '../constants';

import SocialItem from './SocialItem';

describe('SocialItem', () => {
  test('renders social item with correct title and icon', () => {
    const { getByAltText, getByText } = render(
      <SocialItem item={testSocialLink} />
    );

    const titleElement: HTMLElement = getByText(testSocialLink.title);
    expect(titleElement).toBeInTheDocument();

    const imageElement: HTMLElement = getByAltText(testSocialLink.title);
    expect(imageElement).toBeInTheDocument();
  });
});
