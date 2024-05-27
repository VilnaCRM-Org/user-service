import { render } from '@testing-library/react';
import React from 'react';

import { testSocialLink } from '../../features/landing/components/AuthSection/constants';
import SocialItem from '../../features/landing/components/AuthSection/SocialItem/SocialItem';

describe('SocialItem', () => {
  test('renders social item with correct title and icon', () => {
    const { getByAltText, getByText } = render(<SocialItem item={testSocialLink} />);

    const titleElement: HTMLElement = getByText(testSocialLink.title);
    expect(titleElement).toBeInTheDocument();

    const imageElement: HTMLElement = getByAltText(testSocialLink.title);
    expect(imageElement).toBeInTheDocument();
  });
});
