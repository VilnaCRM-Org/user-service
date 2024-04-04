import { render } from '@testing-library/react';

import MainImage from '../../features/landing/components/AboutUs/MainImage/MainImage';

const mainImageTestId: string = 'Main image';

describe('MainImage component', () => {
  it('renders the MainImage component with correct alt text', () => {
    const { getByAltText } = render(<MainImage />);

    expect(getByAltText(mainImageTestId)).toBeInTheDocument();
  });
});
