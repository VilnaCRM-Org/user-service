import { useTheme } from '@mui/material/styles';

export function useCustomBreakpoints() {
  const theme = useTheme();
  const xs = theme.breakpoints.values.xs;
  const sm = theme.breakpoints.values.sm;
  const md = theme.breakpoints.values.md;
  const lg = theme.breakpoints.values.lg;
  const xl = theme.breakpoints.values.xl;

  return {
    xs,
    sm,
    md,
    lg,
    xl,
  };
}
