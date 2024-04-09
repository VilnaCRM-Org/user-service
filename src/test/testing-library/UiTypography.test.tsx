import { render } from '@testing-library/react';
import React from 'react';

import { UiTypography } from '@/components';

import { testText } from './constants';

describe('UiTypography', () => {
  it('should render the Typography component with the correct props', () => {
    const { getByText } = render(
      <UiTypography component="a" variant="h1">
        {testText}
      </UiTypography>
    );

    const typography: HTMLElement = getByText(testText);
    expect(typography).toBeInTheDocument();
  });

  it('should render the Typography component with the default props', () => {
    const { getByText } = render(<UiTypography>{testText}</UiTypography>);

    const typography: HTMLElement = getByText(testText);
    expect(typography.tagName).toBe('P');
  });
});
