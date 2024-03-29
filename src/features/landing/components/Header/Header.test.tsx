import { render } from '@testing-library/react';

import Header from './Header';

const logoAlt: string = 'Vilna logo';

describe('Header component', () => {
  it('renders logo', () => {
    const { getByAltText } = render(<Header />);
    expect(getByAltText(logoAlt)).toBeInTheDocument();
  });
});
