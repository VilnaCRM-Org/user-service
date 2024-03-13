export type CardItem = {
  type: string;
  id: string;
  imageSrc: string;
  title: string;
  text: string;
  alt: string;
};

export interface CardList {
  cardList: CardItem[];
}
