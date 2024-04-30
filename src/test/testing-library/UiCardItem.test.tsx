import { render } from '@testing-library/react';

import UiCardItem from '../../components/UiCardItem';
import CardContent from '../../components/UiCardItem/CardContent';

import { cardItem, largeCard, smallCard } from './constants';

describe('UiCardItem Component', () => {
  const integrateText: string = 'Integrate';
  const servicesText: string = 'services';

  describe('CardComponent', () => {
    it('renders correctly with large card', () => {
      const { getByText } = render(
        <CardContent item={cardItem} isSmallCard={false} />
      );

      const titleElement: HTMLElement = getByText(cardItem.title);
      const textElement: HTMLElement = getByText(cardItem.text);

      expect(titleElement).toBeInTheDocument();
      expect(textElement).toBeInTheDocument();
    });

    it('renders correctly with small card', () => {
      const { getByText } = render(<CardContent item={cardItem} isSmallCard />);

      const titleElement: HTMLElement = getByText(cardItem.title);
      const integrateElement: HTMLElement = getByText(integrateText);
      const servicesElement: HTMLElement = getByText(servicesText);

      expect(titleElement).toBeInTheDocument();
      expect(integrateElement).toBeInTheDocument();
      expect(servicesElement).toBeInTheDocument();
    });
  });

  describe('UiCardItem', () => {
    const cardWrapperTestId: string = 'cardWrapper';
    const cardContentTestId: string = 'cardContent';
    const cardImageTestId: string = 'cardImage';

    it('renders UiCardItem with small card style', () => {
      const { getByTestId } = render(<UiCardItem item={smallCard} />);

      const cardWrapper: HTMLElement = getByTestId(cardWrapperTestId);
      const cardContent: HTMLElement = getByTestId(cardContentTestId);

      expect(cardWrapper).toBeInTheDocument();
      expect(cardContent).toBeInTheDocument();
    });

    it('renders UiCardItem with large card style', () => {
      const { getByTestId } = render(<UiCardItem item={largeCard} />);

      const cardWrapper: HTMLElement = getByTestId(cardWrapperTestId);
      const cardContent: HTMLElement = getByTestId(cardContentTestId);

      expect(cardWrapper).toBeInTheDocument();
      expect(cardContent).toBeInTheDocument();
    });

    it('renders correct UiImage', () => {
      const { getByTestId } = render(<UiCardItem item={cardItem} />);

      const cardImage: HTMLElement = getByTestId(cardImageTestId);

      expect(cardImage).toBeInTheDocument();
      expect(cardImage).toHaveAttribute('alt', cardItem.alt);
    });
  });
});
