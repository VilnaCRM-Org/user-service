import { render } from '@testing-library/react';
import React from 'react';

import { UiFooter } from '@/components/UiFooter';

describe('UiFooter Component', () => {
  it('renders DefaultFooter component with provided social links', () => {
    const { getByTestId } = render(<UiFooter />);

    const defaultFooter: HTMLElement = getByTestId('default-footer');
    expect(defaultFooter).toBeInTheDocument();
  });

  it('renders Mobile component with provided social links', () => {
    const { getByTestId } = render(<UiFooter />);

    const mobileComponent: HTMLElement = getByTestId('mobile-component');
    expect(mobileComponent).toBeInTheDocument();
  });
});
