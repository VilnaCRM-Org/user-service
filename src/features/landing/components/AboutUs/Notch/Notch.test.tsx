import { render } from '@testing-library/react';
import '@testing-library/jest-dom';

import Notch from './Notch';

const notchTestId: string = 'notch';

test('renders notch', () => {
  const { getByTestId } = render(<Notch />);
  expect(getByTestId(notchTestId)).toBeInTheDocument();
});
