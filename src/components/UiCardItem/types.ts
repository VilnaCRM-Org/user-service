export type ImageItem = {
  alt: string;
  image: string;
};
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
  imageList?: ImageItem[];
}
