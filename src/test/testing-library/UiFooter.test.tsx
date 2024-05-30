import { render } from '@testing-library/react';
import React from 'react';

import UiFooter from '@/components/UiFooter';

const stackElementClass: string = '.MuiStack-root';
const containerElementClass: string = '.MuiContainer-root';

describe('UiFooter Component', () => {
  it('renders DefaultFooter component with provided social links', () => {
    const { container } = render(<UiFooter />);

    const footerElement: HTMLElement | null = container.querySelector('footer');
    const defaultFooterWrapper: HTMLElement | null = container.querySelector(stackElementClass);

    expect(footerElement).toBeInTheDocument();
    expect(defaultFooterWrapper).toBeInTheDocument();
  });

  it('renders Mobile component with provided social links', () => {
    const { container } = render(<UiFooter />);

    const footerElement: HTMLElement | null = container.querySelector('footer');
    const mobileWrapper: HTMLElement | null = container.querySelector(containerElementClass);

    expect(footerElement).toBeInTheDocument();
    expect(mobileWrapper).toBeInTheDocument();
  });
});
