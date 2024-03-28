import { render } from '@testing-library/react';

import '@testing-library/jest-dom';
import Header from './Header';

const logoAlt: string = 'header.logo_alt';

describe('Header component', () => {
  it('renders logo', () => {
    const { getByAltText } = render(<Header />);
    expect(getByAltText(logoAlt)).toBeInTheDocument();
  });
});
