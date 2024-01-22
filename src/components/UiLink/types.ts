import { LinkProps } from '@mui/material/Link';

export interface UiLinkProps {
  props?: LinkProps;
  href?: string;
  children: React.ReactNode;
}
