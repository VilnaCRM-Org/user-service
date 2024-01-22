export interface ICardItem {
  type: 'large' | 'small';
  item: {
    id: string;
    imageSrc: string;
    title: string;
    text: string;
    alt: string;
  };
  imageList?: {
    alt: string;
    image: string;
  }[];
}
