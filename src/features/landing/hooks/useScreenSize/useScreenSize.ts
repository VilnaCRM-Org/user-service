import { useMediaQuery } from '@mui/material';
import {
  useCustomBreakpoints,
} from '@/features/landing/hooks/useCustomBreakpoints/useCustomBreakpoints';
import { useTheme } from '@mui/material/styles';

export function useScreenSize() {
  const theme = useTheme();
  const { xs, sm, md, lg, xl } = useCustomBreakpoints();

  const isDesktop = useMediaQuery(theme.breakpoints.up('xl'));
  const isLaptop = useMediaQuery(theme.breakpoints.between('lg', 'xl'));
  const isTablet = useMediaQuery(theme.breakpoints.between('md', 'lg'));
  const isMobile = useMediaQuery(theme.breakpoints.between('sm', 'md'));
  const isSmallest = useMediaQuery(theme.breakpoints.down('sm'));

  return {
    isDesktop,
    isLaptop,
    isTablet,
    isMobile,
    isSmallest,
  };
}
