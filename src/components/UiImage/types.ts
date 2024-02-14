import { ImageProps } from 'next/image';

export interface UiImageProps extends ImageProps {
  sx?: React.CSSProperties;
}
