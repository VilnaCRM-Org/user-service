export type CardItem = {
  id: string;
  imageSrc: string;
  title: string;
  text: string;
  alt: string;
};

export type ImageItem = {
  alt: string;
  image: string;
};

export interface ICardItemProps {
  type: 'large' | 'small';
  item: CardItem;
  imageList?: ImageItem[];
}
