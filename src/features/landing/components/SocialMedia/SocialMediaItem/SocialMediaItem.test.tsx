import { render } from '@testing-library/react';
import React from 'react';

import Logo from '../../../assets/svg/logo/Logo.svg';
import { SocialMedia } from '../../../types/social-media/index';

import SocialMediaItem from './SocialMediaItem';

const item: SocialMedia = {
  linkHref: 'https://twitter.com/',
  ariaLabel: 'Example Link',
  type: 'drawer',
  icon: Logo,
  alt: 'Example Alt Text',
  id: '1',
};

const ariaLabel: string = 'Example Link';
const linkHref: string = 'https://twitter.com/';
const logoWidth: string = '24';
const logoHeight: string = '24';
const logoAlt: string = 'Example Alt Text';

describe('SocialMediaItem', () => {
  it('renders social media icon with correct attributes', () => {
    const { getByRole } = render(<SocialMediaItem item={item} />);

    const linkElement: HTMLElement = getByRole('link', {
      name: ariaLabel,
    });
    expect(linkElement).toBeInTheDocument();
    expect(linkElement).toHaveAttribute('href', linkHref);

    const imageElement: HTMLElement = getByRole('img');
    expect(imageElement).toBeInTheDocument();
    expect(imageElement).toHaveAttribute('alt', logoAlt);
    expect(imageElement).toHaveAttribute('width', logoWidth);
    expect(imageElement).toHaveAttribute('height', logoHeight);
  });
});
