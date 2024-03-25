import { NavItemProps } from '../../../types/header/navigation';

export interface NavListProps {
  navItems: NavItemProps[];
  handleClick?: () => void;
}
