/* eslint-disable react/jsx-props-no-spreading */
import { Link, LinkProps } from '@mui/material';

import defaultLink from './DefaultLink';

function UiLink(props: LinkProps): React.ReactElement {
  const { children } = props;
  return <Link {...props}>{children}</Link>;
}

export const DefaultLink: React.FC<LinkProps> = defaultLink(UiLink);

export default UiLink;
