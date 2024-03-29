import { render } from '@testing-library/react';

import DeviceImage from './DeviceImage';

const notchTestId: string = 'notch';
const mainImageTestId: string = 'main-image';

describe('DeviceImage component', () => {
  test('renders without crashing', () => {
    render(<DeviceImage />);
  });

  test('renders MainImage component', () => {
    const { getByTestId } = render(<DeviceImage />);
    expect(getByTestId(mainImageTestId)).toBeInTheDocument();
  });

  test('renders Notch component', () => {
    const { getByTestId } = render(<DeviceImage />);
    expect(getByTestId(notchTestId)).toBeInTheDocument();
  });
});
