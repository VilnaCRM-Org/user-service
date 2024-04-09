import { render } from '@testing-library/react';
import React from 'react';

import { UiFooter } from '@/components/UiFooter';

const defaultTestId: string = 'default-footer';
const mobileTestId: string = 'mobile-component';

describe('UiFooter Component', () => {
  it('renders DefaultFooter component with provided social links', () => {
    const { getByTestId } = render(<UiFooter />);

    const defaultFooter: HTMLElement = getByTestId(defaultTestId);
    expect(defaultFooter).toBeInTheDocument();
  });

  it('renders Mobile component with provided social links', () => {
    const { getByTestId } = render(<UiFooter />);

    const mobileComponent: HTMLElement = getByTestId(mobileTestId);
    expect(mobileComponent).toBeInTheDocument();
  });
});
