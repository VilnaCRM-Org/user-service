import { ImageProps } from 'next/image';

export interface UiImageProps extends ImageProps {
  sx?: Record<string, unknown>;
}
