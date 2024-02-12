export type ImageItem = {
  alt: string;
  image: string;
};
export type CardItem = {
  id: string;
  imageSrc: string;
  title: string;
  text: string;
  alt: string;
};

export interface CardList {
  type?: 'large' | 'small';
  cardList: CardItem[];
  imageList?: ImageItem[];
}
