import { faker } from '@faker-js/faker';
import { render } from '@testing-library/react';
import React from 'react';

import { UiLink } from '@/components';

const testUrl: string = faker.internet.url();
const randomText: string = faker.lorem.word(8);

describe('UiLink', () => {
  it('renders the Link with the provided children and href', () => {
    const testHref: string = testUrl;
    const { getByText } = render(<UiLink href={testHref}>{randomText}</UiLink>);
    const linkElement: HTMLElement = getByText(randomText);
    expect(linkElement).toBeInTheDocument();
    expect(linkElement).toHaveAttribute('href', testHref);
  });

  it('applies the theme provided to the Link', () => {
    const { getByText } = render(<UiLink href={testUrl}>{randomText}</UiLink>);
    const linkElement: HTMLElement = getByText(randomText);
    expect(linkElement).toBeInTheDocument();
  });
});
