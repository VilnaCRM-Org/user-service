import { UiImageProps } from '../types';

export default function defaultImage(
  Component: React.ComponentType<UiImageProps>
) {
  return function DefaultImage(props: UiImageProps): React.ReactElement {
    const { src, alt, sx } = props as UiImageProps;
    return <Component src={src} alt={alt} sx={sx} />;
  };
}
