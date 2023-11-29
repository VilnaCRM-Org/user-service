import { useTheme } from '@mui/material/styles';

export default function useCustomBreakpoints() {
  const theme = useTheme();
  const { xs } = theme.breakpoints.values;
  const { sm } = theme.breakpoints.values;
  const { md } = theme.breakpoints.values;
  const { lg } = theme.breakpoints.values;
  const { xl } = theme.breakpoints.values;

  return {
    xs,
    sm,
    md,
    lg,
    xl,
  };
}
