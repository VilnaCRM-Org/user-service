import { render } from '@testing-library/react';

import Notch from '../../features/landing/components/AboutUs/Notch/Notch';

const notchTestId: string = 'notch';

test('renders notch', () => {
  const { getByTestId } = render(<Notch />);
  expect(getByTestId(notchTestId)).toBeInTheDocument();
});
