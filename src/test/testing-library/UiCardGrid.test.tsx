import { render } from '@testing-library/react';
import React from 'react';

import CardGrid from '@/components/UiCardList/CardGrid';

import { cardList, largeCardList, smallCardList } from './constants';

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

  it('renders with smallGrid style when cardList[0].type is smallCard', () => {
    const { container } = render(<CardGrid cardList={smallCardList} />);

    const gridElement: ChildNode | null = container.firstChild;
    const computedStyles: CSSStyleDeclaration = window.getComputedStyle(gridElement as Element);

    expect(computedStyles).toHaveProperty('gridTemplateColumns');
  });

  it('renders with largeGrid style when cardList[0].type is largeGrid', () => {
    const { container } = render(<CardGrid cardList={largeCardList} />);

    const gridElement: ChildNode | null = container.firstChild;
    const computedStyles: CSSStyleDeclaration = window.getComputedStyle(gridElement as Element);

    expect(computedStyles).toHaveProperty('gridTemplateColumns');
  });
});
