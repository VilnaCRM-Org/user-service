import { render } from '@testing-library/react';

import AboutUs from '../../features/landing/components/AboutUs/AboutUs';

test('renders without errors', () => {
  const { container, getByRole } = render(<AboutUs />);

  const aboutUsWrapper: HTMLElement | null = container.querySelector('section');
  const headingElement: HTMLElement = getByRole('heading');
  const linkElement: HTMLElement = getByRole('link');

  expect(headingElement).toBeInTheDocument();
  expect(linkElement).toBeInTheDocument();
  expect(aboutUsWrapper).toBeInTheDocument();
});
