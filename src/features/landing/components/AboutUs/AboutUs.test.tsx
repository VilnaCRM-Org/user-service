import { render } from '@testing-library/react';
import '@testing-library/jest-dom';

import AboutUs from './AboutUs';

test('renders without errors', () => {
  render(<AboutUs />);
});
