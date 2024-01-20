import { ImageProps } from 'next/image';

export interface UiImageProps {
  src: string;
  alt: string;
  rest?: ImageProps;
  sx?: object;
}
