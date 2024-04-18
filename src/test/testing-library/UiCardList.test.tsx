import { render } from '@testing-library/react';
import React from 'react';

import UiCardList from '@/components/UiCardList';

import { cardList } from './constants';

jest.mock('../../components/UiCardList/CardSwiper', () =>
  jest.fn(() => <div data-testid="card-swiper" />)
);

describe('UiCardList component', () => {
  it('renders CardSwiper with correct props', () => {
    const { getByTestId } = render(<UiCardList cardList={cardList} />);

    const cardSwiper: HTMLElement = getByTestId('card-swiper');
    expect(cardSwiper).toBeInTheDocument();
  });
});
