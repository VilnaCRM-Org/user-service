import { faker } from '@faker-js/faker';
import { render } from '@testing-library/react';
import React from 'react';

import { UiTypography } from '@/components';

const randomText: string = faker.lorem.word(8);

describe('UiTypography', () => {
  it('should render the Typography component with the correct props', () => {
    const { getByText } = render(
      <UiTypography component="a" variant="h1">
        {randomText}
      </UiTypography>
    );

    const typography: HTMLElement = getByText(randomText);
    expect(typography).toBeInTheDocument();
  });

  it('should render the Typography component with the default props', () => {
    const { getByText } = render(<UiTypography>{randomText}</UiTypography>);

    const typography: HTMLElement = getByText(randomText);
    expect(typography.tagName).toBe('P');
  });
});
