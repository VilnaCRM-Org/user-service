import { useMediaQuery } from '@mui/material';
import { useTheme } from '@mui/material/styles';

const SMALL_TABLET_HIGHER_LIMIT = 850;

export function useScreenSize() {
  const theme = useTheme();

  const isDesktop = useMediaQuery(theme.breakpoints.up('xl'));
  const isLaptop = useMediaQuery(theme.breakpoints.between('lg', 'xl'));
  const isTablet = useMediaQuery(theme.breakpoints.between('md', 'lg'));
  const isBigTablet = useMediaQuery(theme.breakpoints.between(SMALL_TABLET_HIGHER_LIMIT, 'lg'));
  const isSmallTablet = useMediaQuery(theme.breakpoints.between('md', SMALL_TABLET_HIGHER_LIMIT));
  const isMobile = useMediaQuery(theme.breakpoints.between('sm', 'md'));
  const isSmallest = useMediaQuery(theme.breakpoints.down('sm'));

  return {
    isDesktop,
    isLaptop,
    isTablet,
    isMobile,
    isSmallest,
    isSmallTablet,
    isBigTablet,
  };
}
