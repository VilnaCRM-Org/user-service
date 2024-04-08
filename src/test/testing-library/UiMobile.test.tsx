import { render } from '@testing-library/react';
import React from 'react';

import { Mobile } from '@/components/UiFooter/Mobile';

import { SocialMedia } from '../../features/landing/types/social-media';

const mockedSocialLinks: SocialMedia[] = [
  {
    id: 'Instagram-link',
    icon: '',
    alt: 'Alt',
    linkHref: 'https://www.instagram.com/',
    ariaLabel: 'AriaLabel',
  },
];
const mockedDate: number = new Date().getFullYear();
const defaultFooterTestId: string = 'mobile-component';
const logoAlt: string = 'Vilna logo';
const copyright: RegExp = /Copyright/;

describe('DefaultFooter', () => {
  it('should render the component correctly', () => {
    const { getByAltText, getByText, getByTestId } = render(
      <Mobile socialLinks={mockedSocialLinks} />
    );

    expect(getByTestId(defaultFooterTestId)).toBeInTheDocument();
    expect(getByAltText(logoAlt)).toBeInTheDocument();
    expect(getByText(copyright)).toBeInTheDocument();
    expect(getByText(mockedDate.toString())).toBeInTheDocument();
  });
});