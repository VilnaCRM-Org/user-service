export interface ICardList {
  type?: 'large' | 'small';
  cardList: {
    id: string;
    imageSrc: string;
    title: string;
    text: string;
    alt: string;
  }[];
  imageList?: {
    alt: string;
    image: string;
  }[];
}
