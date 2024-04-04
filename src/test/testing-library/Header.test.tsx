import { render } from '@testing-library/react';

import Header from '../../features/landing/components/Header/Header';

const logoAlt: string = 'Vilna logo';

describe('Header component', () => {
  it('renders logo', () => {
    const { getByAltText } = render(<Header />);
    expect(getByAltText(logoAlt)).toBeInTheDocument();
  });
});
