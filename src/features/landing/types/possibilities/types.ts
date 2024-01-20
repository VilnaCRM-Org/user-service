type SmallCardItem = {
  id: string;
  imageSrc: string;
  title: string;
  text: string;
};
type imageListItem = {
  alt: string;
  image: string;
};

export type SmallCardListProps = {
  smallCardItemList: SmallCardItem[];
  imageList: imageListItem[];
};
