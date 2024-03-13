export type CardItem = {
  type: string;
  id: string;
  imageSrc: string;
  title: string;
  text: string;
  alt: string;
};

export interface UiCardItemProps {
  item: CardItem;
}

export interface CardContentProps {
  item: CardItem;
  isSmallCard: boolean;
}
