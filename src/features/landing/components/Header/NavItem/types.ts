import { NavItemProps } from '../../../types/header/navigation';

export interface NavProps {
  item: NavItemProps;
  handleClick?: () => void;
}
