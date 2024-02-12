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

export interface CardItemProps {
  type: 'large' | 'small';
  item: CardItem;
  imageList?: ImageItem[];
}
