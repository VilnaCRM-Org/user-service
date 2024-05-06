import { render } from '@testing-library/react';

import { Possibilities } from '../../features/landing/components/Possibilities';

jest.mock('../../components/UiCardList/CardSwiper', () => jest.fn());

describe('Header component', () => {
  it('renders logo', () => {
    const { container } = render(<Possibilities />);

    const possibilitiesWrapper: HTMLElement | null =
      container.querySelector('#Integration');

    expect(possibilitiesWrapper).toBeInTheDocument();
  });
});
