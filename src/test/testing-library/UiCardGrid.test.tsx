import { render } from '@testing-library/react';
import React from 'react';

import CardGrid from '@/components/UiCardList/CardGrid';

import { cardList } from './constants';

jest.mock('../../components/UiCardItem', () => ({
  __esModule: true,
  default: jest.fn(() => <div data-testid="mock-ui-card-item" />),
}));

describe('CardGrid component', () => {
  it('renders with correct props', () => {
    const { getByTestId } = render(<CardGrid cardList={cardList} />);

    const cardGrid: HTMLElement = getByTestId('mock-ui-card-item');
    expect(cardGrid).toBeInTheDocument();
  });
});
