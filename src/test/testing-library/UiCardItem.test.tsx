import { render } from '@testing-library/react';

import UiCardItem from '../../components/UiCardItem';
import CardContent from '../../components/UiCardItem/CardContent';

import { cardItem, largeCard, smallCard } from './constants';

const cardTitleRole: string = 'heading';

describe('UiCardItem Component', () => {
  describe('CardComponent', () => {
    const integrateText: string = 'Integrate';
    const servicesText: string = 'services';

    it('renders correctly with large card', () => {
      const { getByText, getByRole } = render(<CardContent item={cardItem} isSmallCard={false} />);

      const titleElement: HTMLElement = getByRole(cardTitleRole);
      const textElement: HTMLElement = getByText(cardItem.text);

      expect(titleElement).toBeInTheDocument();
      expect(titleElement).toHaveTextContent(cardItem.title);
      expect(textElement).toBeInTheDocument();
    });

    it('renders correctly with small card', () => {
      const { getByText, getByRole } = render(<CardContent item={cardItem} isSmallCard />);

      const titleElement: HTMLElement = getByRole(cardTitleRole);
      const integrateElement: HTMLElement = getByText(integrateText);
      const servicesElement: HTMLElement = getByText(servicesText);

      expect(titleElement).toBeInTheDocument();
      expect(titleElement).toHaveTextContent(cardItem.title);
      expect(integrateElement).toBeInTheDocument();
      expect(servicesElement).toBeInTheDocument();
    });
  });

  describe('UiCardItem', () => {
    const stackElementClass: string = '.MuiStack-root';

    it('renders UiCardItem with small card style', () => {
      const { container } = render(<UiCardItem item={smallCard} />);

      const element: HTMLElement | null = container.querySelector(stackElementClass);

      expect(element).toBeInTheDocument();
    });

    it('renders UiCardItem with large card style', () => {
      const { container } = render(<UiCardItem item={largeCard} />);

      const element: HTMLElement | null = container.querySelector(stackElementClass);

      expect(element).toBeInTheDocument();
    });

    it('renders correct UiImage', () => {
      const { getByRole } = render(<UiCardItem item={cardItem} />);

      const cardImage: HTMLElement = getByRole('img');

      expect(cardImage).toBeInTheDocument();
      expect(cardImage).toHaveAttribute('alt', cardItem.alt);
    });
  });
});
